# Construction Order 015F — Visual v2: Style Variations / Responsive / Final Polish — 施工報告

- **Status**: IMPLEMENTATION / POLISH COMPLETE
- **Date**: 2026-08-29
- **担当**: クロエ (Chloe)
- **承認元Order**: Construction Order 015F（015Eは公式承認済み前提）
- **Functional Baseline**: RC1 (1.0.0-rc1) — Version番号は変更していない

## 1. 方針

NO NEW FEATURES。今回のCode変更は全て`theme/theme.json`と`theme/styles/{trust,natural,modern}.json`のDesign Token（Color/Radius）調整のみで、新規PHP/JS/Markup/Postmetaはゼロ。Markup/Template/Dynamic Blockは3 Variationで完全共通のまま。

## 2. Pre-polish Audit（要旨）

Trust/Natural/ModernでHOME全Section・Archive/Single・Office/Price/Contactを実Browserで確認し、以下を分類した。

- **A. Variation Identity**：Header/Hero/CardのColor・Typography・Button Radiusは既に3 Variationで明確に差別化されていた（015B/015Cの成果）。
- **D/E. Surface/Radius**：唯一の明確なGapは015C Known IssueのCard Radius共通化——Natural/ModernのButton個性（999px/0px）に対し、Card/Surfaceは全Variation共通の6px固定で、特にModernでは「Sharp Buttonなのに丸いCard」という不整合があった。
- **G. Photography**：Professional Featured Imageが実データで未検証だった（Owner Fixtureに画像なし）。
- **その他（B/C/F/H/I）**：Typography Hierarchy・Section Spacing・Responsive・Visual Noise・Accessibilityの明確なBugは、全Page横断で確認した範囲では見つからなかった（015B〜015Eで既に丁寧に調整済みだったため）。
- **J. Actual Bug**：Contrast計算で、Secondary文字色がTrust/NaturalともWCAG AA基準（4.5:1）未達という実データに基づく明確な問題を発見（後述）。

この分類に基づき、CSSを闇雲に追加するのではなく、Token調整とContrast修正のみに施工を絞った。

## 3. Trust / Natural / Modern — Identity確定

| Variation | Card Radius (Sm/Md) | Button Radius | 特徴 |
|---|---|---|---|
| Trust | 4px / 6px（変更なし） | 2px | 王道・信頼・伝統と現代の中間 |
| Natural | 8px / 14px（新規Token化） | 999px | 柔らかいが、Cardはpill化せず人間的な丸みに留める |
| Modern | 0px / 0px（新規Token化） | 0px | Buttonと揃った完全な直線的Visual Language |

3枚を並べても「色だけ変えました」にも「別Themeです」にも見えず、Markup/Layoutが完全に共通のまま、Radius方針の違いだけで明確に3つの人格が立つことを実機確認した（`docs/research/screenshots/015f/{trust,natural,modern}-radius-check.png`）。

## 4. Contrast Audit — 発見と修正（実バグ）

実際の相対輝度計算（WCAG式）で、以下がAA基準（通常文字4.5:1）未達と判明した。

| 項目 | 修正前 | 修正後 |
|---|---|---|
| Trust secondary on base | 3.06:1 | 4.98:1 |
| Trust secondary on surface | 2.78:1 | 4.52:1 |
| Natural secondary on base | 2.77:1 | 4.95:1 |
| Natural secondary on surface | 2.52:1 | 4.51:1 |
| Natural primary（白文字Button背景） | 3.63:1 | 4.55:1 |

Trust secondary `#8a94a6`→`#69707e`、Natural secondary `#c98a5e`→`#916344`、Natural primary `#7a8c6a`→`#6b7b5d`——いずれも同一色相のまま暗く調整。実機で「所在地」等のMuted Labelの視認性向上、Natural電話番号ButtonのContrast改善を確認した。Modern/Trust Primaryは元々基準を満たしており変更していない。Order §56の「Visual v2が露出させた既存の明確なBugで小規模」に該当する範囲内の修正と判断した。

## 5. Photography

Owner Fixtureに、自作のCSSグラデーション＋頭文字1文字による抽象Avatar画像（実在の人物写真ではない、権利上安全）を3名分作成し、うち2名（佐藤・田中）にFeatured Imageとして設定、1名（鈴木）は意図的に無指定のまま残し、「画像あり/なし混在」状態を作った。

- Professional Archive（Trust/768px/1440px）で2列Gridが崩れないことを確認。
- Professional Single（Trust）でCircular Photo（160px）+ Name/Qualificationの横並びHeaderが正しく機能することを確認。
- 追加コードは一切不要（Construction Order 003/015Bの既存Featured Image Slotがそのまま機能）。

## 6. RESULTS Long Value（015C LOW解消）

RESULTS投稿の`astrea_result_value`を一時的に「累計相談実績 1,234,567件以上（2011年創業以来）」へ差し替え、1440px/375pxで確認。Truncate/Ellipsis/Line-clamp/Parsingは一切発生せず、自然に複数行へWrapすることを確認した。3列Grid内で他の短い値との高さバランスは崩れるが、これは一般的なMetric Componentが極端な長文コンテンツに対して許容すべき自然なTrade-offであり、無理なFont自動縮小等の追加ロジックは導入しなかった（NO NEW FEATURES原則）。検証後、値は元の「500件以上」へ復元済み。

## 7. Header 015B LOW再評価

1440px/1024px/768pxで長い事務所名（特定行政書士法人ASTREA総合法務コンサルティンググループ東京丸の内本部事務所）に差し替えて再検証。1440pxではBorder-rightが名前の直後に自然に位置し、以前報告されていた「Whitespaceへ浮く」感覚は今回の実機確認では再現しなかった。1024pxでは極端な長さの場合のみ2行へ折り返すが、これは許容範囲の自然な劣化と判断。通常の短い事務所名では1024px/768pxともに1行で余裕を持って収まることを確認し、Header自体の追加修正は不要と判断した。

## 8. Responsive — 320/375/768/1024/1440px

HOME・Service/Professional/CASE Archive・Service/Professional/CASE Single・Office/Price/Contactの計10ページ×5幅=50通りを`scrollWidth`自動検査し、Horizontal Overflowは0件だった。768pxでのCard Grid（Service/Professional Archive）も2列で自然なバランスを確認、「1.5列」や「異常に細い2列」は発生していない。

## 9. Core OFF / Search / 404

astrea-core無効化状態でHOME/Office/Price/Contact/Search/404の6経路を確認、全てFatalなし・200または404の正しいStatusを返すことを確認した（直後に再有効化）。Search/404自体への大規模Visual変更は行っていない（Finding 7には未着手）。

## 10. Automated Regression

- PHP Syntax：問題なし（変更ファイルはJSON 4本のみ、PHPは無変更）。
- PHPUnit：359 tests / 560 assertions、OK。
- PHPCS：65/65、0 errors/warnings。
- Theme Check：REQUIRED/WARNING 0（INFO 1件のみ、既知許容事項）。
- Block Validation：HOME/Office/Price/Contactを開き、Invalid Content警告 0件。
- Smoke：CI（Clean環境）に委ねる。今回はPHP/Markup変更が無いため、smoke-test.shとの衝突リスクは極めて低いと判断している。

## 11. Known Issues / Post v1 Backlog（変更なし、維持）

- CPT Archiveの`og:url`がHOME URLを返す既存挙動（015D発見）——引き続き対応せず。
- Price Group Finding 8（Bucket化・再ソート）——引き続き対応せず。
- Search Breadcrumb Finding 7——今回もスコープ外のまま。
- RESULTSの極端な長文Valueは、3列Grid内の高さバランスが崩れる自然なTrade-offとして許容（§6参照）。

## 12. Visual Score（自己評価、甘く採点しない）

| 対象 | 評価 |
|---|---|
| Trust | 87 |
| Natural | 86 |
| Modern | 87 |
| Mobile | 86 |
| Desktop | 87 |
| Whole-site | 87 |

Radius Token化とContrast修正により、015E時点からWhole-siteで+3点相当の改善と判断する。Naturalは依然としてOffice Hours等のMuted表現がやや控えめに見える点を保守的に評価し87ではなく86とした。

## 13. Owner Fixtureへの変更（製品コードではない）

- Professional 3名中2名（佐藤健一・田中美咲）にVerification専用の自作Avatar画像をFeatured Imageとして設定。1名（鈴木大輔）は意図的に無指定のまま維持し、「画像あり/なし混在」の恒久的な検証状態とした。**Order §50の明示的許可に基づき、この状態は今後のOwner Acceptance（015G）でもそのまま使用できるFixture Assetとして残す。**
- Long Japanese Stress検証のため一時的に事務所名を差し替えた際、Backup/Restoreのファイルパスをホスト側とコンテナ内で取り違えるミスがあり、`astrea_core_office_profile`オプションが一瞬空文字列相当になった（フロントエンドは`get_office_profile()`の既存の安全なDefaultフォールバックにより実際にはFatalしなかったが、診断用の直接アクセスコマンド自体はCritical Errorを起こした）。直後に正しいホスト側Backupから完全復元し、全フィールド（事務所名・住所・電話番号・営業時間・SNSリンク・休業例外）が元通りであることを確認済み。以後、同様のBackup/Restore操作は必ずコンテナ内パスへ`docker cp`してから読み込む方式に統一した。
- RESULTS投稿の`astrea_result_value`を一時的に長文へ差し替え、検証後に元の値へ復元済み。

## 14. 変更ファイル一覧

- `theme/theme.json`（`settings.color.palette`のsecondary色調整のみ）
- `theme/styles/trust.json`（`settings.custom.border`追加、secondary色調整）
- `theme/styles/natural.json`（`settings.custom.border`追加、primary/secondary色調整）
- `theme/styles/modern.json`（`settings.custom.border`追加）
- `docs/specifications/06_astrea_visual_v2_design_system.md`（Variation Identity/Radius/Contrast/Photographyの実装結果を反映）
