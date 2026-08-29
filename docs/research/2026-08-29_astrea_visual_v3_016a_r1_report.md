# Construction Order 016A-R1 — Visual v3 Mobile Hero / CASE Refinement — Report

- **Status**: DESIGN REVISION COMPLETE — RELEASE HOLD維持、Owner Approval待ちでSTOP
- **Date**: 2026-08-29〜30
- **担当**: クロエ (Chloe)
- **Product Code Diff**: **ゼロ**（`theme/`・`core/`・`functions.php`・`theme.json`・`styles/*.json`・`patterns/*.php`・`templates/*.html`・`parts/*.html` 全て無変更）
- **前提**: Construction Order 016A（B+C Visual DNA・Desktop Hero方向性・Services・Results・Professional）はOwner承認済み。本Orderはその中の3点（Desktop Hero可読性・Mobile Hero・CASE）のみを再設計する部分改訂。

## 0. 変更範囲の確認（Diff検証）

`visual-v3-home-desktop.html`（016A原本、削除せず保持）に対する`visual-v3-home-desktop-r1.html`（本R1）の差分をライン単位で確認したところ、変更箇所は以下の3ブロックのみに限定されていることを確認した：

1. Desktop Hero CSS（Photography境界・Text Safe Area・Meta Label関連）
2. Mobile Hero CSS + HTML構造（767px以下のMedia Query）
3. CASE Section CSS + HTML（Editorial化）

**Services・Results・Professional のCSS・HTMLは1行も変更していない**（`diff`で該当行範囲に差分0件を確認済み）。Owner指示「承認済み部分を再設計してはならない」を機械的に検証した。

## 1. Desktop Hero — Minor Revision

### 問題

Supporting Copy（「会社設立・許認可・相続——…」）の一部が、写真の明暗境界・建物領域と競合し、実データ次第では可読性が不安定になり得た。

### 対応（H1サイズ・Hero高さ・全体構図は無変更）

- `object-position`を`60% 55%`→`64% 52%`へ微調整し、建物の密集領域をText Columnからさらに離した。
- Supporting Copyの`max-width`を460px→400pxへ縮小し、各行の右端が空・低コントラスト領域から確実に外れるようにした。
- Supporting Copy・Secondary CTAへごく薄い白Text-shadow（`text-shadow:0 1px 16px rgba(251,250,247,.9)`相当）を追加——輪郭のはっきりしたBox/Borderではなく、影のような柔らかい滲みのみ。
- Text Column左側に、非常に薄い角度付きScrim（`.hero-textscrim`、左端で最大不透明度.40、Text Column終端手前で完全に透明化）を追加し、実データの長さ変動に対する安全マージンとした。写真全体を暗くする効果は無い（不透明度・範囲とも局所的）。
- 縦書きMeta Label（"ADMINISTRATIVE SCRIVENER OFFICE — SINCE 2011"）の色を`rgba(16,42,67,.55)`→`.82`へ濃くし、同様の白Text-shadowを追加——最も建物のテクスチャが濃い位置に文字があったため、これも可読性改善対象とした。

### 検証

`hero-desktop-1440-r1.png`で確認: H1サイズ・位置・Hero高さは016A原本と同一。Supporting Copyは写真の明るい空領域内に収まり、Meta Labelも明瞭に判読できる。「普通の2-columnへ戻す」「白Boxを敷く」「強いgradientで写真を殺す」のいずれも行っていない。

## 2. Mobile Hero — Primary Revision

### 問題

016A原本のMobile Heroは「Desktopの正しいResponsive変換」ではあったが、写真を単なる背景（下からのGradient Scrim付き全面写真＋Text重ね）として扱っており、B+CのVisual Impactが弱かった。

### 新しい構図：「写真右上ブリード＋Textプレート・オーバーラップ」

Owner提示候補（A/B/C）のうち、候補B（Photography upper/right crop + Typography lower/left overlap）の思想を土台に、ASTREA向けに具体化した：

- 写真（`hero-office-city.png`）を画面右側80%幅・高さ220px（`min-height:220px;max-height:300px`のClamp）でTop-right配置。左端20%は写真が無く、Header/背景色のみ。
- その直下に、不透明な白背景のTextプレート（Eyebrow/H1/Copy/CTA一式）を配置し、**上方向に55pxオーバーラップ**させて写真の右下角に重ねた。これによりDesktopの非対称構図とは異なる、Mobile固有の非対称・視覚的緊張を作った。
- Textは写真の上ではなく不透明な白背景の上に乗るため、**Scrim/Text-shadowなしで完全なコントラスト**を確保——読みやすさとPhotography Impactの両立を、対立させずに解決した。
- Header（Identity + 電話番号 + Hamburger）は写真の上にTransparentで乗る。
- CTA階層: Primary（お問い合わせ、濃紺Fill、`order:1`）→ Secondary（電話番号、Outline、`order:2`）を明確に分離。
- 最下部に「01 SERVICES」の次Section Hintを配置。

### 実装中に発見した問題と修正（記録）

`.hero-content`に`margin-top`（オーバーラップ量）を指定した際、`.hero`が`overflow:visible`のままだと**CSSのMargin Collapsing（親子間の上マージン相殺）**が発生し、指定した`margin-top`が`.hero`自身の外側マージンへ「抜けて」しまい、Header・写真・Textが全て同じY座標に重なって表示される不具合が発生した。`.hero`に`overflow:hidden`（Block Formatting Context確立）を指定して解決——これはMockup構築時の実装知見であり、016B実装時に同種のOverlap構図を採用する場合は同じ対策が必要になる。

### 検証

- 375px: `hero-mobile-375-r1.png`。H1「複雑な手続きを、／前へ進める力に。」は指定通り2行、意味単位の破綻なし。
- 320px: `hero-mobile-320-r1.png`。同様に2行を維持、Horizontal Overflow無し。
- Playwright実測で320px/375pxともOverflow 0件。

## 3. CASE — Editorial Hierarchy化

### 問題

ProfessionalまでのVisual品質に対し、CASEが急に一般的な「白背景・角丸・影・Thumbnail・Title・Description」を3回繰り返す従来型Card一覧に戻って見えていた。

### CASE Asset Limitation（重要、016Aから継続する制約）

現有のCASE候補画像3点はいずれも実寸512×260px前後で、大きなFeature表示（数百px超への拡大）には解像度不足——**低解像度画像をCSSで無理に拡大した完成案は作らなかった**。

R1では、Feature/Secondaryの区別を「画像サイズの差」ではなく「Typography・Row Treatmentの差」で作ることで、**現有Assetのまま**（画像は全て原寸以下で表示、アップスケールなし）解像度問題を回避しつつEditorial Hierarchyを実現した：

| 項目 | Feature（CASE 01） | Secondary（CASE 02/03） |
|---|---|---|
| Number | 2.3rem | 1.7rem |
| Title | 1.55rem | 1.05rem |
| 画像表示サイズ | 260×160px（原寸513×260以下） | 190×120px（原寸512×260以下） |
| 行の余白 | 46px | 38px |

各行は、Number（Serif、Gold）／Body（Category・Serviceラベル＋Title＋Description＋Link）／Media（画像 or 空Placeholder）の3カラムGrid＋細い罫線区切りという、Servicesセクションと共通する編集的リズムを持つ。白背景・角丸・影のCard表現は使用していない。

### No-Photo Fallback（実演）

CASE 03（相続手続き）はあえて画像を割り当てず、`.case-media.is-empty`（Paper Warm背景＋Gold細線円のみ）を表示し、**No-Photo Fallbackが実際に成立することをMockup内で実演**した。Number・Rule・Label・Title・Description・Linkのみで、他の2件と並んでも違和感なく完結して見える。Mockup下部に、これが意図的な実演である旨の注記を付けた。

### High-Res Asset Specification（将来、より大きなFeature演出を行う場合に必要な仕様）

現行R1は現有Assetで完結する設計だが、Owner/Codemi側が将来より大きなFeature Case演出（Desktop Heroに匹敵する迫力のあるCASE Feature）を求める場合の素材仕様を、Mockupでの実測に基づき以下のとおり定義する：

| 項目 | 仕様 |
|---|---|
| Aspect Ratio | 3:2 または 16:10（Feature用）／既存の2:1でも可（Secondary用） |
| 推奨最小幅 | Feature: 1600px以上／Secondary: 800px以上 |
| Subject | 実際の業務内容を象徴する情景（例: 会社設立=オフィス外観や書類、建設業許可=現場や図面、相続=書斎や家族の情景）を推奨。現行の建物外観・法律書類・都市風景は「業種を象徴する情景」として機能するが、より具体的な被写体（人物の後ろ姿での相談風景等）はさらに強い訴求力を持つ可能性がある |
| Composition | Featureは横長ワイド（Text Overlay無しの独立画像として使うか、Desktop Heroのような非対称配置も可） |
| Crop Safe Area | 現行R1の設計（画像内に文字を焼き込まない）を維持するなら、Safe Area定義は不要。将来Text Overlay方式（016A原本のOverlay案）を採用する場合は、画像下1/3を暗め・低コントラストな構図にすることを推奨 |
| Text-overlay Safe Area要否 | **現行R1設計では不要**（Textは画像の外、Bodyカラムに独立配置されているため） |

## 4. Before / After 比較（016A original vs 016A-R1）

| 評価項目 | 016A | 016A-R1 |
|---|---|---|
| Desktop Hero readability | 中（写真境界と本文が近接、実データ次第で不安定） | 高（Safe Area・Text-shadow・Scrimで実データ変動に耐性） |
| Mobile First View Impact | 中（Responsive変換として正しいが平板） | 高（写真ブリード＋Textプレート・オーバーラップで独自の非対称構図） |
| Mobile Photography Presence | 低〜中（背景としての写真、Scrimで大きく減衰） | 高（写真は原色のまま、独立した視覚要素として存在） |
| Mobile CTA Hierarchy | 中（Primary/Secondaryの区別はあったがVisual Weightが近い） | 高（`order`で明確な優先順位、Secondaryは輪郭のみ） |
| CASE Feature Hierarchy | 低（3枚均等Card、Feature/Secondaryの差が視覚的に弱い） | 高（Type ScaleとRow Treatmentで明確な主役・脇役構造） |
| CASE Editorial Character | 低（白背景・角丸・影のCard） | 高（Number・Rule・Labelを持つEditorial List、Servicesと呼応） |
| CASE No-Photo Resilience | 未検証（3件とも画像前提の構図） | 実演済み（CASE 03で意図的に無画像、完全に成立） |

## 5. Implementation Feasibility（R1追加分）

| 要素 | 分類 |
|---|---|
| Desktop Hero Text Safe Area Scrim | B（Theme CSS） |
| Desktop Hero Text-shadowによる可読性補強 | B（Theme CSS） |
| Mobile Hero 写真ブリード＋Overlapプレート構図 | B（Theme CSS）＋Markup構造の見直し（Header/Hero Template Part側の実装次第でC相当になる可能性あり、016B確認事項） |
| CASE Editorial行（Number/Label/Rule） | C（既存`astrea/case-list` Dynamic BlockのMarkup拡張が必要、016Aから継続する見立てのまま） |
| CASE No-Photo Fallback（`.is-empty`枠） | C（同上のBlock拡張に含めて設計可能、新規データ不要——既存のFeatured Image有無で分岐するだけ） |
| High-Res CASE Asset | D（Asset置き換え、実施するか否かはOwner判断） |

## 6. Out of Scope（今回も対応せず、維持）

price-list limit属性欠如／CPT Archive og:url／Professional Archive空Excerpt／Search Breadcrumb汎用ラベル／Price Group構造Finding。いずれもRelease前Backlogとして維持する。

## 7. Completion Conditions

- [x] Desktop Hero readability改善
- [x] Desktop Hero impact維持（H1サイズ・Hero高さ・全体構図を変更していないことをDiff検証済み）
- [x] Mobile Hero B+C impact改善
- [x] 375px確認（Overflow 0件、H1 2行維持）
- [x] 320px確認（Overflow 0件、H1 2行維持）
- [x] Mobile CTA hierarchy改善
- [x] CASE Editorial hierarchy改善
- [x] CASE no-photo fallback設計（Mockup内で実演）
- [x] High-res CASE asset requirement定義
- [x] Implementation feasibility分類
- [x] Before/After比較
- [x] Product code diff 0（`git status`で確認）
- [ ] HISTORY更新（本Report確定後に実施）
- [ ] Commit / Push / CI Green（同上）
- [x] RELEASE HOLD継続
- [x] Owner Approval待ちでSTOP
