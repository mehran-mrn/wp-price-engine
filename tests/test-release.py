import importlib.util
from pathlib import Path
import tempfile
import unittest
import zipfile

spec = importlib.util.spec_from_file_location('release', Path(__file__).resolve().parents[1] / 'tools/prepare-release.py')
release = importlib.util.module_from_spec(spec)
spec.loader.exec_module(release)


class ReleaseTests(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        self.files = {
            'mrn-gold-currency-pricing/mrn-price-engine.php': "<?php\n/**\n * Version: 1.4.1\n * Text Domain: mrn-gold-currency-pricing\n */\ndefine( 'MRNPE_VERSION', '1.4.1' );\n",
            'mrn-gold-currency-pricing/readme.txt': 'Contributors: mrnwp\nStable tag: 1.4.1\n',
            'mrn-gold-currency-pricing/LICENSE': 'GPLv3 or later',
            'mrn-gold-currency-pricing/guide-fa.txt': 'Guide',
            'mrn-gold-currency-pricing/NOTICE.txt': 'Attributions',
            'mrn-gold-currency-pricing/services.txt': 'External services',
            'mrn-gold-currency-pricing/uninstall.php': '<?php',
        }

    def package(self):
        package = self.root / 'plugin.zip'
        with zipfile.ZipFile(package, 'w') as archive:
            for name, content in self.files.items():
                archive.writestr(name, content)
        return package

    def reject(self, tag='1.4.1'):
        with self.assertRaises(ValueError):
            release.prepare(self.package(), self.root / 'output', tag)
        self.assertFalse((self.root / 'output').exists())

    def test_valid_release_preserves_file_contents(self):
        self.assertEqual(release.prepare(self.package(), self.root / 'output', 'v1.4.1'), '1.4.1')
        self.assertEqual((self.root / 'output/readme.txt').read_bytes(), self.files['mrn-gold-currency-pricing/readme.txt'].encode())

    def test_mismatched_release_tag(self):
        self.reject('1.4.2')

    def test_mismatched_stable_tag(self):
        self.files['mrn-gold-currency-pricing/readme.txt'] = 'Contributors: mrnwp\nStable tag: 1.4.0\n'
        self.reject()

    def test_development_documents_are_rejected(self):
        self.files['mrn-gold-currency-pricing/docs/ai/prompt.md'] = 'Development only'
        self.reject()

    def test_path_traversal_is_rejected(self):
        self.files['mrn-gold-currency-pricing/assets/../../outside.txt'] = 'Outside'
        self.reject()

    def test_wrong_contributor_is_rejected(self):
        self.files['mrn-gold-currency-pricing/readme.txt'] = 'Contributors: mehran-mrn\nStable tag: 1.4.1\n'
        self.reject()


if __name__ == '__main__':
    unittest.main()
