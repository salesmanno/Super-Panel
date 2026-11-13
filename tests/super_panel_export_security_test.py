import unittest

from scripts.super_panel_export import TARGET, valid_url


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


if __name__ == "__main__":
    unittest.main()
