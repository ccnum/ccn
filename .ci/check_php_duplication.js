#!/usr/bin/env node

/**
 * Détecte le PHP dupliqué (copier-coller) dans le plugin plugins/thematique,
 * via jscpd (node_modules/.bin/jscpd, installé en dépendance dev npm).
 *
 * Limité à ce seul plugin (pas plugins/fictions ni plugins/petitfablab,
 * contrairement à check_html_duplication.js) : à élargir si besoin une fois
 * la convention validée ici.
 *
 * Fonctionnement identique à check_html_duplication.js (voir ce fichier
 * pour le détail), appliqué aux fichiers *.php à la place de *.html :
 * - Lance jscpd sur plugins/thematique, restreint aux fichiers *.php, avec
 *   un rapport JSON.
 * - Chaque clone est réduit à une signature stable : les deux (chemin
 *   relatif, ligne de départ), triés, plus le nombre de lignes dupliquées.
 * - Compare au fichier de baseline : toute signature absente de la
 *   baseline fait échouer le script (nouvelle duplication introduite).
 *   Une entrée de la baseline qui n'est plus détectée (code corrigé, ou
 *   lignes décalées) est simplement ignorée.
 *
 * Usage (depuis la racine du dépôt) :
 *   node .ci/check_php_duplication.js [--baseline=PATH] [--write-baseline]
 *                                      [--min-lines=N] [--min-tokens=N]
 */

const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');

const repoRoot = path.dirname(__dirname);
const scanRoots = ['plugins/thematique'].map((p) => path.join(repoRoot, p));
const jscpdBin = path.join(repoRoot, 'node_modules', '.bin', 'jscpd');

const args = process.argv.slice(2);
function optValue(name, fallback) {
	const prefix = `--${name}=`;
	const found = args.find((a) => a.startsWith(prefix));
	return found ? found.slice(prefix.length) : fallback;
}
const baselinePath = optValue('baseline', path.join(repoRoot, '.ci', 'php-duplication-baseline.txt'));
const writeBaseline = args.includes('--write-baseline');
const minLines = optValue('min-lines', '5');
const minTokens = optValue('min-tokens', '50');

if (!fs.existsSync(jscpdBin)) {
	console.error(`node_modules/.bin/jscpd introuvable (${jscpdBin}). Lancer \`npm ci\` (jscpd est en devDependency).`);
	process.exit(2);
}

const outDir = fs.mkdtempSync(path.join(os.tmpdir(), 'jscpd-report-'));

// jscpd sort en code 1 des qu'il trouve au moins une duplication : ce
// n'est pas une erreur d'execution, on lit le rapport JSON ensuite.
try {
	execFileSync(jscpdBin, [
		'--min-lines', minLines,
		'--min-tokens', minTokens,
		'--pattern', '**/*.php',
		'--ignore', '**/vendor/**',
		'--reporters', 'json',
		'--output', outDir,
		'--silent',
		...scanRoots,
	], { stdio: ['ignore', 'inherit', 'inherit'], cwd: repoRoot });
} catch {
	// code de sortie != 0 attendu quand des clones sont trouves
}

const reportPath = path.join(outDir, 'jscpd-report.json');
if (!fs.existsSync(reportPath)) {
	console.error(`jscpd n'a pas produit de rapport JSON (${reportPath}).`);
	process.exit(2);
}

const report = JSON.parse(fs.readFileSync(reportPath, 'utf8'));

function relPath(p) {
	return path.isAbsolute(p) ? path.relative(repoRoot, p) : p;
}

const current = new Set();
for (const dup of report.duplicates || []) {
	const occurrences = [
		`${relPath(dup.firstFile.name)}:${dup.firstFile.startLoc.line}`,
		`${relPath(dup.secondFile.name)}:${dup.secondFile.startLoc.line}`,
	].sort();
	current.add(`${dup.lines}L | ${occurrences.join(' <-> ')}`);
}
const currentSorted = Array.from(current).sort();

if (writeBaseline) {
	fs.writeFileSync(baselinePath, currentSorted.length ? currentSorted.join('\n') + '\n' : '');
	console.log(`Baseline écrite : ${baselinePath} (${currentSorted.length} entrée(s))`);
	process.exit(0);
}

let baseline = new Set();
if (fs.existsSync(baselinePath)) {
	baseline = new Set(
		fs.readFileSync(baselinePath, 'utf8')
			.split('\n')
			.map((l) => l.trim())
			.filter(Boolean)
	);
}

const newClones = currentSorted.filter((c) => !baseline.has(c));

if (newClones.length) {
	console.log('Duplication PHP détectée (absente de la baseline) :\n');
	for (const c of newClones) {
		console.log(`  ${c}`);
	}
	console.log('\nFactorise ce code (fonction partagée dans thematique_fonctions.php, cf. convention du projet), ou si c\'est une duplication acceptée pour l\'instant, régénère la baseline avec --write-baseline.');
	process.exit(1);
}

console.log(`OK — aucune nouvelle duplication PHP (${currentSorted.length} entrée(s) déjà connue(s) en baseline).`);
process.exit(0);
