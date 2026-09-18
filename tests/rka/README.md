# RKA template checks

## Oracle LR: complete workpaper realization rules

The LR importer is separate from the RKA calculator. One atomic upload reads KANWIL, SURABAYA, KEDIRI, MALANG, MADIUN and BANYUWANGI. It requires the B7/D7/H7 headers and retains H (Ending Balance) exactly. Administrator-managed mappings classify the three Excel helper groups KUR, NON KUR and PEN. The realization engine then applies the audited workpaper formulas: direct Description LOB product sums for guarantee/claim rows, premium-mix allocation for ordinary NON KUR rows, formula-derived subtotals and profit, six-unit corporate consolidation, and actual/RKA percentages. Description COA matching is exact after case/whitespace normalization. Unknown numeric rows remain in the unmapped audit. The two verified Oracle spellings, Tranportasi dinas dalam negeri and Tranportasi dinas luar negeri, map to their report labels while retaining the source spelling for audit. Missing accounts stay absent/dashes, explicit numeric zero stays zero, and an RKA denominator of zero displays 0%. Existing stored uploads are recalculated with current built-in formula mappings, including verified aliases, without requiring the same workbook to be uploaded again.

The nine audited result-row formulas are owned by the realization calculation engine and are not editable from Seting Rumus. Subtotals, net guarantee income, operating expenses and profit before tax are therefore calculated consistently for every unit and LOB. Previously stored `accounting_lr_formula_rules` overrides are ignored by the calculation engine; Korporat Kanwil remains the consolidation of the six calculated source units.

Temporary Oracle source corrections are stored separately in `accounting_lr_source_adjustments`. An administrator selects the unit, LOB, detail row, start/end month, exact raw Oracle descriptions and the reason. Active periods for the same target cannot overlap, the maximum duration is 24 months, every create/update/deactivation is audited, and the standard source mapping resumes automatically outside the selected period. This permits a documented Surabaya reserve correction without weakening the standard formula for later months or other branches.

Accounting users can submit create, update and deactivation requests through the same searchable Oracle-description picker. Requests are stored in `accounting_lr_source_adjustment_requests` and do not affect report calculations while pending. Only an Administrator can approve or reject them; approval revalidates periods and source descriptions, refuses stale rule versions, applies the requested change, and records both requester and reviewer. Rejected requests remain as history without changing any active rule.

Branch guarantee and claim rules use the source COAs approved from the workpaper. Premium revenue is sign-inverted for KUR, NON KUR and optional PEN. The reserve row uses only Kenaikan/penurunan estimasi liabilitas klaim - penjaminan in every branch; Surabaya's erroneous penjaminan ulang reserve is excluded. Optional PEN and Beban Pajak PPh 21 Non Karywan remain blank when absent. The PPh source contributes only to NON KUR subrogation. KBG/Suretyship, Konsumtif and Produktif remain report outputs, not new base filters: direct rows use Description LOB and ordinary rows use the premium mix. Kanwil has no fabricated guarantee/claim values and uses the consolidated branch premium mix for product allocation.

LR amounts retain their source signs and all source decimal digits using LrMoney/BCMath decimal strings. Fractions beyond cents, including Oracle serialization residue, are retained rather than normalized or rounded. Addition uses the largest operand scale so no intermediate digits are truncated. Bounded input validation (22 integer digits, up to 100 fractional digits, exponent up to 30) rejects unsupported inputs instead of shortening them. Formula/text/date cells cannot supply the matched H amount. User workbooks are never modified. RKA's separate two-decimal policy remains unchanged.

V4 records persist both the original numeric string and a full-precision canonical amount. Older v1/v2/v3 reports also recompute from their retained source_amount strings, not their previously rounded amount/total caches, so full precision applies without rewriting user records. The existing v3 segments remain recognized; v1/v2 still require a fresh upload to add NON KUR.

V5 introduced the Kanwil sign audit. The current realization engine applies the workpaper sign to Pendapatan jasa giro and Pendapatan lainnya in every report unit: negative becomes positive, positive becomes negative and zero remains zero. The raw source string stays unchanged. The single unsegmented Oracle LABA SEBELUM PAJAK row remains an audit input; the displayed profit is always recalculated from its approved upstream rows.

V6 retains V5's Kanwil-only policy and adds two exact-label rules across every worksheet: LABA TAHUN BERJALAN and JUMLAH LABA KOMPREHENSIF TAHUN BERJALAN. Every matching numeric source H value is stored with sheet, row, source_amount and the sign-inverted calculation_amount in all_sheet_sign_rule_inputs. The importer resolves worksheets by relationship ID rather than workbook order, scans each safely, rejects duplicate target labels within one sheet, and never changes the source workbook. Sheets that do not contain a target are permitted. The supplied workbook reconciles two targets in each of its six sheets (12 audited values). These are future calculation inputs, not product-segment output cells.

LR report and source-audit presentation use nearest whole Rupiah with halves away from zero. Only the final display is rounded, never the operands or persisted values. Negative reports keep the leading red asterisk, including negative source fractions that display as *0. Exact amounts are available in the table's data-lr-exact attribute and native hover title; missing values remain dashes. Financial tests reconcile every raw source decimal and every display result independently using Python Decimal, and exercise fractional summation across a rounding threshold.

Uploads require an explicit month and year, both of which must match the period parsed from every required sheet. Private source copies and import history are retained. For a selected unit/year, the latest YTD month and latest upload within that month are used, rather than adding multiple uploads together. The report recomputes its sum from the audited source rows instead of trusting a stored total. Upload errors leave previous imports unchanged.

```text
php spark migrate
php tests/rka/oracle_lr_salary.php --source
php tests/rka/oracle_lr_database.php
node tests/rka/oracle_lr_upload.cjs
node tests/rka/oracle_lr_delete.cjs
python tests/rka/oracle_lr_reconcile.py
python tests/rka/oracle_lr_global_sign_reconcile.py
php tests/rka/laba_rugi_filter.php
php tests/rka/oracle_lr_mapping.php
php tests/rka/lr_formula_settings.php
php tests/rka/oracle_lr_realization.php
python tests/rka/oracle_lr_branch_reconcile.py
python tests/rka/oracle_lr_realization_reconcile.py
```

The source test is read-only against the supplied LR workbook. The database test uses guarded, unused year 2097 and rolls back all test records. `--write-preview` additionally produces a synthetic, non-submitting browser fixture; it does not upload the user's workbook or retain report data.

Deletion requires server-side confirmation and an exact unit/month/year selection. One locked transaction soft-deletes every upload revision for that period; other months, units, years, and RKA are unchanged. Source files are retained, and the existing administrator Data Terhapus screen supports restoration. Tests exercise missing confirmation, period validation, revision history, isolation, and administrator recovery.

The authoritative schema comes from Sheet1 of Template RKA.xlsx. Monetary inputs and persisted values are decimal strings, never binary floating-point numbers. PHP requires BCMath, ZipArchive and SimpleXML; the manual preview requires browser BigInt support.

Edit and manual setup share the same source-only form. Formula results (all TOTAL cells and calculated product subtotals/profit) are non-editable output spans with a gray background and an automatic-calculation tooltip. They update from source inputs and are never submitted as editable money. Render tests verify no formula cell is an input, and server tests reject forged formula-result fields.

All amounts are full Rupiah (Rp), not thousands/millions. Negative report outputs use `*absolute amount` without parentheses; the leading asterisk is red and display-only. Editable inputs retain `-amount`, and stored values, formula arithmetic and corporate consolidation retain their negative signs and exact cents. Zero/empty outputs remain a centered dash, and positive outputs remain unchanged. PHP report formatting and browser-calculated outputs are tested against the same exact-money cases.

Run from the project root:

```text
php tests/rka/run.php --fixtures
node tests/rka/preview.cjs
node tests/rka/selection.cjs
node tests/rka/delete.cjs
node tests/rka/upload.cjs
php tests/rka/render.php
php tests/rka/database.php
php tests/rka/bulk.php
php tests/rka/bulk_database.php
php tests/rka/api.php
```

The database check requires the configured local database and the new migration. It rolls back its temporary test records and refuses to replace an existing test-period record. For deployment, run `php spark migrate` before opening the RKA menu.

The workbook has 675 editable inputs and 181 formula cells. Checks reconcile 858 source amounts, exercise precision, signs, altered formulas, extra monetary rows, overflow and stale revisions. The browser preview uses integer cents and is checked against the same server cases.

Template rollups (each product column):

```text
14 = 11 - 12 - 13
21 = 17 + 18 - 19 - 20
23 = 14 - 21
52 = SUM(29:51)
142 = SUM(55:141)
160 = SUM(145:159)
162 = 52 + 142 + 160
166 = 23 + 25 - 162 + 164
```

The commission subtraction follows the supplied workbook exactly. Every TOTAL adds the five products, including rows 25 and 164, whose original H cells are literal zero rather than formulas. The original downloadable workbook is not rewritten.

Uploads accept the original audited .xlsx layout or the supplied blank Template_RKA_JMK_KV_SBY.xlsx layout, up to 5 MB, with matching year and labels. The F:K financial layout supports both header row 6 and the current working-paper position on row 5; for the latter, business rows 7–165 are mapped one row down to the unchanged internal schema. This preserves existing database identities and formulas while matching the working paper's visible row positions. The original layout requires intact formulas; the blank layout (C:H headers on row 5) permits missing formulas, but any supplied formula must still match the audit. Formula caches are not trusted: totals are recalculated on the server. Empty inputs count as zero; negative values stay negative; nonzero precision beyond two decimals is rejected. The upload popup no longer offers template downloads.

Single-unit upload requires one Sheet1 or one sheet matching the selected source unit. All-unit upload requires exactly seven sheets named Korporat Kanwil, Kanwil, Surabaya, Kediri, Malang, Madiun, Banyuwangi (case-insensitive, no extra spaces or other sheets). The old C:H and new F:K monetary layouts are supported, with A2/D2 year and unchanged audited rows. New-layout A:C account codes are metadata, D/E are labels. The supplied Kanwil export has 26 missing calculated formulas: K8/K11:K13/K17:K20 and F14:K14/F21:K21/F23:K23; only these missing formulas are allowed, and present formulas must still match. Its literal K173 duplicate of K166 is permitted only when equal; other out-of-structure values are rejected. Standard printerSettings binary records are allowed, but macros/external links/other binary entries remain rejected.

Corporate F:J input formulas must reference the same cell in each of the six source units exactly once. They are validated, never executed; corporate results are recomputed from parsed unit inputs rather than Excel caches. Corporate is not a seventh additive source. The corporate view always consolidates current active records for the selected year in one source query; unit edits, soft deletion and administrator restoration are reflected on the next view load without a separate corporate upload. Corporate manual/single-unit overrides and deletion are blocked. All-unit import persists the consolidated corporate snapshot, but that snapshot never overrides the live aggregate.

Worksheet order never determines the target unit. Unknown, duplicate or missing names produce an alert listing the problem; no unit is saved until all sheets validate. Persistence is one atomic transaction with per-unit stored identity/revision snapshots and explicit replacement confirmation. Bulk tests use unused year 2098; failed last-unit writes must really roll back, and successful test batches run inside a transaction that is rolled back. Database tests also verify automatic corporate updates, deletion/restoration and exact cents without retaining any test records.
