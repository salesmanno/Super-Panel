"""Real disposable Git configuration, with all export/transport simulated."""

import contextlib
import fcntl
import importlib.util
import io
import os
from pathlib import Path
import subprocess
import tempfile
import tomllib
import unittest
from unittest import mock


ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location(
    "super_panel_export", ROOT / "scripts/super_panel_export.py"
)
exporter = importlib.util.module_from_spec(spec)
spec.loader.exec_module(exporter)
REAL_RUN = subprocess.run
WRITES = {"add", "rm", "commit", "pull", "push", "init", "reset"}


class ExportTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix="super-panel-git-test-")
        self.addCleanup(self.temp.cleanup)
        self.repo = Path(self.temp.name)
        self.old_cwd = Path.cwd()
        self.addCleanup(os.chdir, self.old_cwd)
        # Test Git must never inherit the project's repository/auth configuration.
        self.env = {k: v for k, v in os.environ.items() if not k.startswith("GIT_")}
        self.env.update(
            HOME=self.temp.name, GIT_CONFIG_NOSYSTEM="1",
            GIT_CONFIG_GLOBAL="/dev/null", GIT_TERMINAL_PROMPT="0",
        )
        self.environment = mock.patch.dict(os.environ, self.env, clear=True)
        self.environment.start()
        self.addCleanup(self.environment.stop)
        self.git("init", "-b", "main")
        self.git("remote", "add", "origin", exporter.TARGET)
        self.git("config", "branch.main.remote", "origin")
        self.git("config", "branch.main.merge", "refs/heads/main")
        os.chdir(self.repo)
        self.commands = []

    def git(self, *args):
        return REAL_RUN(
            ("git", *args), cwd=self.repo, env=self.env, check=True,
            stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True,
        )

    def transport(self, *, fail=None, change_after_commit=False):
        def invoke(command, **kwargs):
            args = list(command)
            if tuple(args[:len(exporter.GIT)]) == exporter.GIT:
                args = args[len(exporter.GIT):]
            else:
                raise AssertionError("Unexpected Git execution")
            if args[0] not in WRITES:
                return REAL_RUN(command, **kwargs)
            self.commands.append(args)
            if change_after_commit and args[0] == "commit":
                self.git("config", "remote.origin.url", "https://github.com/salesmanno/www.git")
            # Deliberately sensitive synthetic diagnostics must never be printed.
            return subprocess.CompletedProcess(
                command, 1 if fail == args[0] else 0,
                stdout=b"https://FAKE-NOT-A-SECRET@github.com/salesmanno/www.git",
                stderr=b"transport diagnostics",
            )
        return mock.patch.object(exporter.subprocess, "run", side_effect=invoke)

    def test_check_is_read_only(self):
        before = (self.repo / ".git/config").read_bytes()
        with self.transport():
            exporter.validate_target()
        self.assertEqual([], self.commands)
        self.assertEqual(before, (self.repo / ".git/config").read_bytes())
        self.assertFalse((self.repo / ".git/super-panel-export.lock").exists())

    def test_existing_authenticated_origin_is_preserved_and_not_printed(self):
        authenticated = "https://FAKE-NOT-A-SECRET@github.com/salesmanno/Super-Panel.git"
        self.git("config", "remote.origin.url", authenticated)
        before = (self.repo / ".git/config").read_bytes()
        output = io.StringIO()
        with mock.patch.object(os.sys, "argv", ["export", "check"]), contextlib.redirect_stdout(output):
            self.assertEqual(0, exporter.main())
        self.assertIn(exporter.TARGET, output.getvalue())
        self.assertNotIn("FAKE-NOT-A-SECRET", output.getvalue())
        self.assertEqual(before, (self.repo / ".git/config").read_bytes())

    def test_other_repository_owner_host_protocol_and_deceptive_urls_block_all_actions(self):
        for url in [
            "https://github.com/salesmanno/www.git",
            "https://github.com/salesmanno/tenant_copy-company-panel-2.git",
            "https://github.com/other/Super-Panel.git",
            "https://example.com/salesmanno/Super-Panel.git",
            "https://github.com.evil.example/salesmanno/Super-Panel.git",
            "https://github.com@evil.example/salesmanno/Super-Panel.git",
            "http://github.com/salesmanno/Super-Panel.git",
            "ssh://git@github.com/salesmanno/Super-Panel.git",
            exporter.TARGET + "?redirect=www.git",
            exporter.TARGET + "#www.git",
            "https://github.com:444/salesmanno/Super-Panel.git",
            exporter.TARGET + "\nhttps://github.com/salesmanno/www.git",
        ]:
            with self.subTest(url=url):
                self.git("config", "remote.origin.url", url)
                for action in ["sync-push", "reindex-push"]:
                    with self.transport(), self.assertRaises(exporter.ExportBlocked):
                        exporter.export(action)
                self.assertEqual([], self.commands)
                self.assertFalse((self.repo / ".git/super-panel-export.lock").exists())

    def test_extra_fetch_url_blocked(self):
        self.git("config", "--add", "remote.origin.url", exporter.TARGET)
        with self.assertRaises(exporter.ExportBlocked):
            exporter.validate_target()

    def test_explicit_correct_pushurl_allowed(self):
        self.git("config", "remote.origin.pushurl", exporter.TARGET)
        exporter.validate_target()

    def test_wrong_or_multiple_pushurls_blocked(self):
        self.git("config", "remote.origin.pushurl", "https://github.com/salesmanno/www.git")
        with self.assertRaises(exporter.ExportBlocked):
            exporter.validate_target()
        self.git("config", "remote.origin.pushurl", exporter.TARGET)
        self.git("config", "--add", "remote.origin.pushurl", exporter.TARGET)
        with self.assertRaises(exporter.ExportBlocked):
            exporter.validate_target()

    def test_rewrites_blocked_even_if_current_url_would_resolve_correctly(self):
        for key in [
            "url.https://github.com/salesmanno/www.git.insteadOf",
            "url.https://github.com/salesmanno/www.git.pushInsteadOf",
            "url.https://github.com/.insteadOf",
        ]:
            with self.subTest(key=key):
                self.git("config", key, exporter.TARGET)
                with self.assertRaises(exporter.ExportBlocked):
                    exporter.validate_target()
                self.git("config", "--unset-all", key)

    def test_unexpected_branch_and_push_defaults_blocked(self):
        for key, value in [
            ("branch.main.remote", "other"),
            ("branch.main.pushRemote", "other"),
            ("branch.main.merge", "refs/heads/other"),
            ("remote.pushDefault", "other"),
            ("remote.origin.mirror", "true"),
            ("remote.origin.vcs", "custom"),
            ("remote.origin.receivepack", "custom"),
            ("remote.origin.uploadpack", "custom"),
            ("remote.origin.push", "refs/heads/main:refs/heads/other"),
        ]:
            with self.subTest(key=key):
                old = self.git("config", "--get-all", key).stdout if key in (
                    "branch.main.remote", "branch.main.merge"
                ) else None
                self.git("config", key, value)
                with self.assertRaises(exporter.ExportBlocked):
                    exporter.validate_target()
                self.git("config", "--unset-all", key)
                if old:
                    self.git("config", key, old.strip())
        self.git("symbolic-ref", "HEAD", "refs/heads/not-main")
        with self.assertRaises(exporter.ExportBlocked):
            exporter.validate_target()

    def test_url_specific_redirects_blocked(self):
        key = "http.https://github.com/.followRedirects"
        self.git("config", key, "true")
        with self.assertRaises(exporter.ExportBlocked):
            exporter.validate_target()
        self.git("config", key, "false")
        exporter.validate_target()

    def test_missing_origin_and_invalid_url_blocked_without_git_error_leak(self):
        self.git("remote", "remove", "origin")
        with self.assertRaises(exporter.ExportBlocked):
            exporter.validate_target()
        for invalid in ["https://[bad/salesmanno/Super-Panel.git", "", "https://github.com:bad/path"]:
            self.assertFalse(exporter.valid_url(invalid))

    def test_internal_remotes_and_unused_target_variable_unchanged(self):
        self.git("remote", "add", "gitsafe-backup", "git://gitsafe:5418/backup.git")
        self.git("remote", "add", "subrepl-example", "git+ssh://git@internal/workspace")
        before = (self.repo / ".git/config").read_bytes()
        with mock.patch.dict(os.environ, {"TARGET_REPO": "github.com/salesmanno/www.git"}):
            exporter.validate_target()
        self.assertEqual(before, (self.repo / ".git/config").read_bytes())

    def test_git_commands_override_recursive_submodule_transport(self):
        self.git("config", "push.recurseSubmodules", "on-demand")
        self.git("config", "fetch.recurseSubmodules", "true")
        self.git("config", "http.followRedirects", "true")
        self.assertEqual(["no"], exporter.read_git("config", "--get", "push.recurseSubmodules"))
        self.assertEqual(["false"], exporter.read_git("config", "--get", "fetch.recurseSubmodules"))
        self.assertEqual(["false"], exporter.read_git("config", "--get", "http.followRedirects"))
        exporter.validate_target()

    def test_sync_sequence_uses_only_validated_origin_main_and_simulated_transport(self):
        with self.transport(), contextlib.redirect_stdout(io.StringIO()):
            exporter.export("sync-push")
        self.assertEqual(["add", "commit", "pull", "push"], [c[0] for c in self.commands])
        self.assertEqual(["push", "-u", "origin", "main"], self.commands[-1])

    def test_reindex_sequence_keeps_existing_ignore_refresh(self):
        (self.repo / "tracked.txt").write_text("disposable")
        self.git("add", "tracked.txt")
        with self.transport(), contextlib.redirect_stdout(io.StringIO()):
            exporter.export("reindex-push")
        self.assertEqual(["rm", "add", "commit", "push"], [c[0] for c in self.commands])
        self.assertEqual(["push", "origin", "main"], self.commands[-1])
        self.assertNotIn("pull", [c[0] for c in self.commands])

    def test_changed_target_after_commit_cannot_reach_pull_or_push(self):
        with self.transport(change_after_commit=True), contextlib.redirect_stdout(io.StringIO()):
            with self.assertRaises(exporter.ExportBlocked):
                exporter.export("sync-push")
        self.assertEqual(["add", "commit"], [c[0] for c in self.commands])

    def test_failure_stops_sequence_and_never_prints_authenticated_git_output(self):
        output = io.StringIO()
        with self.transport(fail="pull"), mock.patch.object(os.sys, "argv", ["export", "sync-push"]):
            with contextlib.redirect_stdout(output), contextlib.redirect_stderr(output):
                self.assertEqual(1, exporter.main())
        self.assertEqual(["add", "commit", "pull"], [c[0] for c in self.commands])
        self.assertNotIn("FAKE-NOT-A-SECRET", output.getvalue())
        self.assertNotIn("transport diagnostics", output.getvalue())

    def test_concurrent_workflow_stops_without_mutating_git(self):
        with (self.repo / ".git/super-panel-export.lock").open("w") as lock:
            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
            with self.transport(), self.assertRaises(exporter.ExportBlocked):
                exporter.export("sync-push")
        self.assertEqual([], self.commands)

    def test_workflows_are_guarded_and_not_auto_started(self):
        workflows = tomllib.loads((ROOT / ".replit").read_text())["workflows"]["workflow"]
        by_name = {w["name"]: w for w in workflows}
        self.assertNotIn("PUSH tenant_copy,company_panel2", by_name)
        for name, action in [
            ("PUSH Super, Company_panel1", "sync-push"), ("One", "reindex-push")
        ]:
            commands = [t.get("args") for t in by_name[name]["tasks"] if t["task"] == "shell.exec"]
            self.assertEqual(["python3 scripts/super_panel_export.py " + action], commands)
        for workflow in workflows:
            for task in workflow.get("tasks", []):
                self.assertNotIn("TARGET_REPO", str(task.get("args", "")))


if __name__ == "__main__":
    unittest.main()
