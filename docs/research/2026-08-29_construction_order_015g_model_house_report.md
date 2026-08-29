# Construction Order 015G — Model House / Owner Visual Acceptance — 施工報告

- **Status**: VISUAL FIXTURE / ACCEPTANCE PREPARATION COMPLETE
- **Date**: 2026-08-29
- **担当**: クロエ (Chloe)
- **承認元Order**: Construction Order 015G（015Fは公式承認済み前提）
- **Functional Baseline**: RC1 (1.0.0-rc1) — Version番号は変更していない
- **Release Status**: **RELEASE HOLD維持**（本報告のみで解除しない）

## 1. 今回の問い、と答え

「現在のOwner Fixtureが殺風景なのは、ASTREA自体の限界か、Fixtureの内容不足か」という問いに対する答え：

**主にFixtureの内容・構成不足だった。** ASTREA FREE標準機能（`core/cover`等のWordPress Core Block、既存Dynamic Block、既存Design Token）だけで、HOMEに明確なVisual Anchor（Hero画像）とSection Rhythm（白背景の連続を断つBreathing Point）を与えたところ、「本気で作った小規模士業サイト」に近い見え方まで到達した。ただし1件、Fixtureでは解消できない構造的な制約（Price一覧の件数制御）を発見し、Productへの改修は行わずFindingとして記録した（§14参照）。

## 2. Backup

施工前に`wp db export`でOwner Fixture全体をDump。

- **保存先（ホスト）**: `.fixture-backups/owner-fixture-pre-015g-20260829-201941.sql`（Git管理対象外、`.gitignore`へ追加済み）
- **取得方法**: `docker exec <cli> wp db export /tmp/<name>.sql` → `docker cp <cli>:/tmp/<name>.sql .fixture-backups/`（ホスト側パスへ確実にコピーしたことを`ls`で確認してから施工開始）
- **Restore手順**（未実施、手順のみ確認）: `docker cp .fixture-backups/<name>.sql <cli>:/tmp/` → `docker exec <cli> wp db import /tmp/<name>.sql --allow-root`

## 3. Product Code Changes

**ゼロ。** `git status`で確認した変更ファイルは`.gitignore`（Fixture Backupの除外設定追加）のみ。`theme/`・`core/`配下は本Orderの間、一切変更していない。

## 4. Fixture Changes（概要）

- HOME（Page ID 1914）の`post_content`を、既存WordPress Core Block（`core/cover`, `core/group`, `core/heading`, `core/paragraph`, `core/buttons`）と既存ASTREA Dynamic Blockの組み合わせのみで再構成（詳細は§5, §6）。
- Hero用の画像1点（自作の抽象都市イラスト、後述）をMedia Libraryへアップロードし、Cover Blockの背景として使用。
- Professional 3名中、未設定だった1名（鈴木大輔）にも検証用Fixture画像を追加し、3名全員がFeatured Imageを持つ状態にした（015Fで意図的に維持していた「画像あり/なし混在」の検証記録はスクリーンショットとして015Fの成果物に残っており、今回は喪失していない）。
- 4点の画像（Hero1点、Professional 3点）に、内容を正しく説明するAlt Textを設定（`_wp_attachment_image_alt`、標準のWordPress Media機能）。
- 上記全てDynamic Block自体・PHP・CSSは無変更。既存の`get_office_profile()`, `get_professional_profile()`, Featured Image Slot（Construction Order 003/015B）がそのまま機能した。

## 5. Hero

**Before**: Surface背景のGroup + H1（office_nameバインド）+ 1行Tagline + Button 2つ、のみ。画像なし、Visual Anchorが弱い。

**After**: `core/cover`ブロックへ変更。背景に自作の都市イラスト（後述§6）、`dimRatio:55`＋`overlayColor:contrast`でTextの可読性を確保、`contentPosition:"center left"`で左寄せ。中身は既存のH1（office_nameバインド維持）＋Tagline（Fixture Copyを具体化、§8参照）＋補足説明1文＋既存の電話/問い合わせButton 2つ（Bindingも維持）。Block Validation実機確認で警告0件。

Trust/Natural/Modernいずれも、Cover Blockの`overlayColor:contrast`が各Variationの`contrast`Token値（濃紺／焦げ茶／黒）を自動的に反映し、**同一コンテンツのまま3つの異なる雰囲気のHeroが成立する**ことを実機確認した（`after-home-{trust,natural,modern}.png`）。

## 6. Photography

Hero画像は、権利不明なWeb画像を使わず、自作のCSS/SVG的手法（PlaywrightでHTML+CSSアニメーションを描画しPNG化）による**完全オリジナルの抽象イラスト**（都市のビル群のシルエット＋暖色の窓明かり）。実在の建物・写真の模倣ではない。Alt Textには「抽象イラスト（検証用Fixture画像）」と明記し、実写真であるかのような誤認を避けた。

Professional 3名の画像も同じ手法（グラデーション背景＋頭文字1文字）による自作の抽象Avatarで、実在人物の写真ではない。Theme配布物には一切含めず、Owner Fixtureの投稿にのみ添付。

## 7. Services

Card DesignはBefore/After共通（Product Code変更なし）。既存の6件の説明文を確認したところ、既に「対象者・範囲・提供内容」が明確に書き分けられており（例:「就労ビザ・経営管理ビザ等、外国人の在留資格に関する申請をサポートします」）、追加のCopy改善は不要と判断した。

## 8. CASE

既存3件の内容（会社設立・建設業許可・相続）を確認したところ、既に「Before(状況)→対応→Result(短期間/初回成功等の具体的な結果)」の構造を1〜2文で満たしており、誇張表現・法的結果保証・不自然な成功率の記載もない。追加改善は不要と判断した。

## 9. RESULTS

既存3 Metric（相談実績500件以上・会社設立支援200社以上・建設業許可取得率98%）は、小規模事務所として現実的な値であり変更不要と判断した。Demo公開時にこれが架空数値であることの明示要否は、Post v1のDemo戦略判断事項として記録に留める（製品コード・Fixture自体は変更していない）。

## 10. Professional

代表者紹介（HOME）・Professional Archive/Singleとも、既存Featured Image Slotのみで写真を反映。名前・資格・紹介文・写真の関係は既存Markup通りで、追加調整は不要だった（§6のPhotography追加のみ）。

## 11. Price

HOMEの`astrea/price-list`は`limit`属性を持たないため、現在登録されている4件のPrice全てが表示される。§14で製品Findingとして記録し、Fixture側での回避（件数を減らす等）は行っていない（実際のPrice Pageで見せるべき情報を削ることになるため）。

## 12. FAQ

HOMEでは`mode:"important"`＋`limit:3`により5件中3件を表示する既存の仕組みをそのまま使用。「初回相談は無料か」「対応エリア」「費用の決まり方」という、相談前に知りたい内容が既に選ばれており変更不要と判断した。

## 13. VOICE

既存3件の内容を確認したところ、既に「不安→対応→安心」の構造を持つ具体的な内容（Lorem Ipsum的な内容ではない）で、過剰な絶賛表現もないため変更不要と判断した。

## 14. Flow / Closing CTA

「ご相談の流れ」（問い合わせ→ヒアリング/見積り→契約・着手）、「まずはお気軽にご相談ください」の濃紺CTAともBefore/Afterで変更なし——既にOrderの意図（次に何をすべきか分かる、CTAが乱立していない）を満たしていたと判断した。

## 15. Section Rhythm（今回の主な変更）

**Before**: Surface(Hero) → 白(Services/CASE/RESULTS/Professional/Price/FAQ/VOICEの7セクション連続) → Surface(Flow) → 濃色(CTA)。中間の白背景が7セクション分連続し、縦方向に間延びして見える状態だった。

**After**: Hero(Cover画像+濃色Overlay) → 白(Services/CASE) → **Surface(RESULTS、新規追加のBreathing Point)** → 白(Professional/Price/FAQ/VOICE) → Surface(Flow) → 濃色(CTA)。既存の`core/group`＋`backgroundColor:"surface"`（Hero/Flowで既に使われていたパターンと同一）でRESULTSセクションを包んだだけで、新規Component・新規CSSは一切追加していない。「実績」の数字がページ中盤の呼吸点として機能することを実機確認した。

## 16. Header

Product Code変更なし。Modelハウス化したHOMEの上に乗せても、015B Header v2はそのまま自然に機能した。

## 17. Footer

Product Code変更なし。Main Content密度が上がった後も、Footerが浮いたり不釣り合いに見えることはなかった。

## 18. Office

既存の015E/015F施工内容（Office Summary Card + Office Hours Table + SNS Chip）がそのままModel House Fixtureとして機能。追加変更なし。

## 19. Service Archive

既存015D施工内容がそのまま機能。6件のServiceが2列Cardで表示され、殺風景さはない。

## 20. Service Single

既存015D施工内容がそのまま機能。Related Content（他のService）・Closing CTAとも正常表示。

## 21. Professional Archive

3名全員に写真が入った状態で2列Cardが完成し、Before（写真なし3件）と比較して明確に「事務所らしさ」が増した。

## 22. Professional Single

代表（佐藤健一）のCircular Photo付きHeaderが機能。既存内容通り。

## 23. CASE Archive

既存3件がPrimary色左BorderのCard Gridで表示、Serviceとの差別化も維持されている。

## 24. CASE Single

Related Content（`related_services`経由のService紐付け、015D/E確認済み）・Closing CTAとも正常表示。

## 25. Price Page

既存4件がStructured Listで表示。§11/§14の`limit`属性欠如はHOME側の問題であり、Price Page自体は「詳細を見せる」という役割を正しく果たしている。

## 26. FAQ Archive

既存5件が左BorderのQ/Aリストで表示。

## 27. VOICE Archive

既存3件がTestimonial Cardで表示。

## 28. Contact

既存015E施工内容（Card化されたForm、Focus可視化等）がそのまま機能。Model House化による影響なし。

## 29. Desktop

1440pxで全ページ確認。Hero追加後もLayout崩れなし。

## 30. Mobile（375px）

Hero画像・Professional写真・Cards・Metrics・CTAいずれもDesktopの縮小版ではなく、Mobile幅に合わせて自然に再配置されることを確認した（`after-home-mobile.png`, `hero-mobile-zoom.png`）。

## 31. 320px

Horizontal Overflow 0件（`scrollWidth`自動検査）。

## 32. Trust

主要仕上げ。Hero/Section Rhythmとも狙い通りの見え方を確認。

## 33. Natural

同一Content・同一MarkupのままStyle Variation切替のみで、暖色・柔らかい印象のHeroへ変化することを確認（`after-home-natural.png`）。

## 34. Modern

同様にシャープで高コントラストな印象のHeroへ変化することを確認（`after-home-modern.png`）。

## 35. Accessibility

- H1唯一性：HOMEのCover内H1（office_nameバインド）のみ、他ページも既存構造のまま1つずつ確認済み。
- 画像Alt：Hero画像・Professional 3画像とも、内容を説明する具体的なAlt Textを新規設定（§4/§6参照、標準のWordPress Media機能のみ使用）。
- Heading階層・Landmark・Link Purpose・Contrast：015B〜015Fで確認済みの水準を後退させる変更はしていない。

## 36. Performance

新規JS 0、外部Font 0、外部Icon Library 0。Hero画像は1600×900pxのPNG（約110KB）で、Web用途として適正なサイズ。巨大画像の投入はしていない。

## 37. Product Findings（製品コードは直していない、Owner判断待ち）

### Finding 1: `astrea/price-list` に `limit` 属性が無い

`astrea/service-list`・`astrea/case-list`・`astrea/results-list`・`astrea/voice-list`は全て`limit`属性を持ち、HOME Teaserでは代表的な件数のみを表示できる。しかし`astrea/price-list`（`core/includes/price-list-block.php`）にはこの属性が無く、HOMEでも常に**全件**表示される。

- **影響**: Order 015G §19「HOMEでは代表的な料金のみ、詳細はPrice Pageへ誘導」が、Price件数が増えるほど実現しづらくなる（現状4件は許容範囲内だが、将来10件以上になった場合、HOMEが料金表化するリスクがある）。
- **対応方針**: 本Orderの Product Code Freeze（§3/§41）に従い、Core側の修正は行っていない。次のVisual/Product Constructionでの検討候補として記録する。

## 38. FREE Capability Verdict

**A. FREE標準機能だけで十分Professionalなサイトを作れる。**

根拠：Hero画像の追加、Section Rhythmの改善とも、新規Block・新規PHP・新規CSSを一切必要とせず、既存のWordPress Core Block（Cover/Group）とASTREA既存Dynamic Blockの「組み合わせ」だけで実現できた。3 Style Variationの切り替えも、Contentを一切変更せずに機能した。

ただし§37のFindingの通り、件数が多い場合のPrice表示制御には現状FREEの標準機能だけでは対応しきれない場面がある（B寄りの限定的な例外）。

## 39. PRO Boundary Hints（仕様策定はしない、記録のみ）

- **FREEで可能だが手間がかかること**: Hero用画像の準備・Cover Block組み立て・Section Rhythm設計（どこにSurface Breakを入れるか）は、WordPress Block Editorの知識がある人には可能だが、初めての利用者には手順として複雑。
- **FREEで簡単なこと**: 既存Dynamic Blockの`heading`/`limit`属性の調整、Fixture文言の差し替え自体は、Editorから数分で行える。
- **PROなら自動化できそうなこと**: Hero組み立てテンプレート（画像+Copy+CTAのセット提案）、業種別Section構成の初期提案、Professional/CASE画像の一括配置ガイド、Price表示件数の自動最適化（§37 Finding含む）。

## 40. BLOCKER

0件。

## 41. HIGH

0件。

## 42. MEDIUM

1件（§37 Finding 1: `astrea/price-list`の`limit`属性欠如）。

## 43. LOW

0件。

## 44. Who

Chloe

## 45. Start

2026-08-29 20:19 JST

## 46. End

2026-08-29 20:55 JST

## 47. Duration

36m

## 48. Commit

（Docs/Screenshot/.gitignoreのみ、後述コミットハッシュ参照）

## 49. Report Path

`docs/research/2026-08-29_construction_order_015g_model_house_report.md`

## 50. Screenshot Path

`docs/research/screenshots/015g-model-house/`

## 51. CI

PHPUnit/PHPCS/Theme Check/Block Validationとも本Orderで変更したProduct Codeが無いため、既存Baseline（359/560, 65/65, INFO1件のみ, 0件）を維持。Smokeテストは本Orderでは製品コード変更が無いため、通常のCI実行で確認する（Owner FixtureへのSmoke実行はしていない）。

## 52. Ready for Owner Inspection

**Yes.**

---

**RELEASE HOLDは維持します。** Final Releaseへは進みません。Ownerが実画面を確認した上での最終判断をお待ちします。
