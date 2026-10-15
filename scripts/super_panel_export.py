#!/usr/bin/env python3
"""Project-owned Git export entry point. Default operation is read-only."""

import argparse
import fcntl
import os
from pathlib import Path
import subprocess
import sys
from urllib.parse import urlsplit


TARGET = "https://github.com/salesmanno/Super-Panel.git"
GIT = (
    "git", "--no-optional-locks", "-c", "http.followRedirects=false",
    "-c", "push.recurseSubmodules=no", "-c", "fetch.recurseSubmodules=false",
)


class ExportBlocked(RuntimeError):
    pass


def read_git(*args, optional=False):
    result = subprocess.run(
        (*GIT, *args), stdin=subprocess.DEVNULL,
        stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True,
    )
    if result.returncode == 1 and optional:
        return []
    if result.returncode:
        # Git diagnostics can contain authenticated URLs. Never echo them.
        raise ExportBlocked("Kunne ikke kontrollere Git-oppsettet. Ingen eksport startet.")
    return result.stdout.splitlines()


def values(key):
    return read_git("config", "--get-all", key, optional=True)


def valid_url(value):
    """Allow only the established HTTPS target, retaining existing auth."""
    try:
        if not value or any(c.isspace() or ord(c) < 32 for c in value):
            return False
        parsed = urlsplit(value)
        return (
            parsed.scheme == "https"
            and parsed.hostname == "github.com"
            and parsed.port is None
            and parsed.path.casefold() == "/salesmanno/super-panel.git"
            and not parsed.query
            and not parsed.fragment
        )
    except ValueError:
        return False


def validate_target():
    if read_git("rev-parse", "--is-inside-work-tree") != ["true"]:
        raise ExportBlocked("Kjør fra prosjektets eksisterende Git-arbeidsområde.")
    if read_git("symbolic-ref", "--quiet", "--short", "HEAD", optional=True) != ["main"]:
        raise ExportBlocked("Eksport er bare tillatt fra eksisterende main-branch.")

    rewrites = read_git(
        "config", "--get-regexp", r"^url\..*\.(insteadof|pushinsteadof)$", optional=True
    )
    if rewrites:
        raise ExportBlocked("Git URL-omskriving er konfigurert. Eksport er stoppet.")

    fetch = values("remote.origin.url")
    push = values("remote.origin.pushurl")
    if len(fetch) != 1 or not valid_url(fetch[0]):
        raise ExportBlocked("origin må ha nøyaktig én adresse til " + TARGET)
    if len(push) > 1 or (push and not valid_url(push[0])):
        raise ExportBlocked("origin har et ukjent eller ekstra push-mål. Eksport er stoppet.")
    for args in [(), ("--push",)]:
        resolved = read_git("remote", "get-url", "--all", *args, "origin")
        if len(resolved) != 1 or not valid_url(resolved[0]):
            raise ExportBlocked("Faktisk Git-mål er ikke det tillatte Super-Panel-repositoryet.")

    for key in ("vcs", "receivepack", "uploadpack"):
        if values("remote.origin." + key):
            raise ExportBlocked("origin har et alternativt transportoppsett. Eksport er stoppet.")
    mirror = read_git("config", "--bool", "--get", "remote.origin.mirror", optional=True)
    if mirror and mirror != ["false"]:
        raise ExportBlocked("Speil-push er ikke tillatt i prosjektets eksport.")
    if values("remote.origin.push"):
        raise ExportBlocked("origin har egne push-refspecs. Eksport er stoppet.")
    for key, expected in (
        ("branch.main.remote", "origin"),
        ("branch.main.merge", "refs/heads/main"),
        ("branch.main.pushRemote", "origin"),
        ("remote.pushDefault", "origin"),
    ):
        configured = values(key)
        if configured and configured != [expected]:
            raise ExportBlocked("main har et uventet mål eller sporingsoppsett. Eksport er stoppet.")

    # Scoped HTTP settings can override the command-level generic setting.
    redirects = read_git(
        "config", "--get-regexp", r"^http\..*\.followredirects$", optional=True
    )
    if any(line.rsplit(None, 1)[-1].casefold() != "false" for line in redirects):
        raise ExportBlocked("URL-spesifikke HTTP-viderekoblinger må være avslått.")


def mutate_git(*args):
    # Re-check after hooks/pull and before every mutation, not just at startup.
    validate_target()
    result = subprocess.run(
        (*GIT, *args), stdin=subprocess.DEVNULL,
        stdout=subprocess.PIPE, stderr=subprocess.PIPE,
    )
    if result.returncode:
        raise ExportBlocked(
            "Git-steget «" + args[0] + "» feilet. Ingen videre steg ble kjørt. "
            "Rå Git-utdata skjules fordi de kan inneholde innloggingsopplysninger."
        )
    print("Fullført: " + args[0], flush=True)


def export(action):
    validate_target()
    common = read_git("rev-parse", "--git-common-dir")
    if len(common) != 1:
        raise ExportBlocked("Kunne ikke finne Git-låsemappen.")
    # Serialize this project's export buttons, without touching refs or index.
    lock_path = Path(common[0]) / "super-panel-export.lock"
    descriptor = os.open(lock_path, os.O_CREAT | os.O_RDWR, 0o600)
    with os.fdopen(descriptor, "w") as lock:
        try:
            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError:
            raise ExportBlocked("En annen Super-Panel-eksport kjører allerede.") from None
        validate_target()
        if action == "reindex-push":
            # Preserve the existing One action, including its ignore-rule refresh.
            if read_git("ls-files"):
                mutate_git("rm", "-r", "--cached", ".")
            message = "Ekskludert alle agent- og minnemapper helt"
        else:
            message = "Lagret før pull"
        mutate_git("add", ".")
        mutate_git("commit", "-m", message, "--allow-empty")
        if action == "sync-push":
            # Existing user-triggered synchronization behaviour is retained.
            mutate_git("pull", "origin", "main", "--rebase", "--allow-unrelated-histories", "-X", "theirs")
            mutate_git("push", "-u", "origin", "main")
        else:
            mutate_git("push", "origin", "main")


def main():
    parser = argparse.ArgumentParser(description="Eksporter bare til " + TARGET)
    parser.add_argument(
        "action", nargs="?", default="check",
        choices=("check", "sync-push", "reindex-push"),
        help="check er kun lesing; de andre valgene utfører eksport når brukeren starter dem.",
    )
    action = parser.parse_args().action
    try:
        if action == "check":
            validate_target()
            print("Kontroll OK: main → " + TARGET + " (ingen endringer utført)")
        else:
            export(action)
        return 0
    except (ExportBlocked, OSError):
        # OSError can contain local paths; keep authentication out of all output.
        error = sys.exc_info()[1]
        print("STOPPET: " + (str(error) if isinstance(error, ExportBlocked)
                              else "Git-kontrollen kunne ikke fullføres."), file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main())
