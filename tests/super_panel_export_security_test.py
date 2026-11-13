import unittest
from types import SimpleNamespace
from unittest.mock import patch

from scripts.super_panel_export import (
    ExportBlocked, TARGET, git_environment, mutate_git, read_git, valid_url,
)


class ExportUrlSecurityTests(unittest.TestCase):
    def test_allows_only_the_expected_clean_target(self):
        self.assertTrue(valid_url(TARGET))
        self.assertTrue(valid_url(
            "https://github.replit.internal/salesmanno/Super-Panel.git",
            resolved=True,
        ))
        self.assertFalse(valid_url("https://github.com/salesmanno/Other.git"))

    def test_rejects_credentials_in_remote_urls(self):
        self.assertFalse(valid_url(
            "https://token@github.com/salesmanno/Super-Panel.git"
        ))
        self.assertFalse(valid_url(
            "https://user:password@github.com/salesmanno/Super-Panel.git"
        ))

    def test_workflow_git_cannot_wait_for_terminal_password(self):
        self.assertEqual(git_environment()["GIT_TERMINAL_PROMPT"], "0")
        with patch("scripts.super_panel_export.subprocess.run") as run:
            run.return_value = SimpleNamespace(returncode=0, stdout="ok\n")
            self.assertEqual(read_git("status"), ["ok"])
            self.assertEqual(run.call_args.kwargs["env"]["GIT_TERMINAL_PROMPT"], "0")

    def test_authentication_failure_is_safe_and_actionable(self):
        with patch("scripts.super_panel_export.validate_target"), \
                patch("scripts.super_panel_export.subprocess.run") as run:
            run.return_value = SimpleNamespace(
                returncode=128,
                stderr=b"fatal: could not read Password: terminal prompts disabled",
            )
            with self.assertRaises(ExportBlocked) as failure:
                mutate_git("push", "origin", "main")
            message = str(failure.exception)
            self.assertIn("GitHub-innlogging", message)
            self.assertIn("Git-panel", message)
            self.assertNotIn("fatal:", message)
            self.assertEqual(run.call_args.kwargs["env"]["GIT_TERMINAL_PROMPT"], "0")


if __name__ == "__main__":
    unittest.main()
