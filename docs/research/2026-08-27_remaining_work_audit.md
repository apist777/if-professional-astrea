# ASTREA FREE v1 — REMAINING WORK AUDIT（残工事総点検 / 009〜Release工程策定）

**種別:** AUDIT（調査のみ。製品コード変更なし。`theme/`/`core/`/`tests/`/`tools/`は未変更）
**Status:** AUDIT COMPLETE
**対象HEAD:** `7c36b99`（Construction 008 COMPLETE時点）
**確認範囲:** docs/specifications/00-05、Decision 001-028、docs/research/全件、HISTORY.csv/HISTORY.md、theme/、core/、tests/、tools/、.github/workflows/

---

## 1. Executive Summary

ASTREA FREE v1は、Core側の主要データ機能（Office Profile／Professional Profile／Service／Price／FAQ／Contact／SEO Foundation／Setup）とTheme側のDesign System基盤（Style Variation／Templates／HOME用Pattern）が実装・Test・CI Green済みである。一方で、以下の3種類の残工事が確認された。

1. **正式仕様に明記されているが未実装の機能**（見落とし）：GA4基本設定、Core完全削除の確認UI（Decision 019）。
2. **意図的に後工程へ持ち越された機能**（Construction 007/008の各報告書で明示済み）：CASE/RESULTS/VOICE、ACCESS固有情報、CTA固有データ、Services HOME Teaserの完全自己非表示。
3. **未着手のRelease工程**：HOME最終組み立て導線、日本語実データ耐久試験、Responsive/Accessibility/Performance総合監査、WordPress.org提出準備、Core完全削除UI、Packaging、Documentation、Release Candidate運用。

さらに、監査の過程で**FREE/PRO境界に関する要確認事項**を1件発見した（§18-1、Construction 007のNavigation生成機能と「Navigation自動構築→PRO」という既存記述の関係）。これは修正せず本書で報告する。

現在の完成率は、機能実装ベースではおよそ75-80%だが、Release品質（Packaging・Documentation・総合監査・WordPress.org準備）を含めると**全体でおよそ55-60%**と評価する（詳細は§17）。

---

## 2. 001〜008完成状況

正式仕様・Decision・実装コード・Test・CI結果を照合した棚卸し。

| Construction | 内容 | 状態 | 明示的な持越し事項 |
|---|---|---|---|
| 001 | 開発基盤／Theme-Core独立性 | **実装済** | なし |
| 002 | Office Profile | **実装済** | なし（Schema Migration機構は実装フェーズ詳細のまま） |
| 003 / 003A | Professional Profile | **実装済** | 代表者複数許可はDecision 025で正式確定済み、持越しなし |
| 004 | Service / Price / FAQ | **実装済** | Price/Offer構造化データ非対応はDecision 026でCLOSED。0件時UIパターン統一はDecision 028でCLOSED |
| 005 | Contact | **実装済** | 添付ファイル機能はv1範囲外と02仕様書で明記済み（持越しではなく仕様上不要） |
| 006 | SEO Foundation | **部分実装** | **GA4基本設定が未実装**（§18-2で詳述、02仕様書§18に明記されているが006の対象に含まれなかった） |
| 007 | Setup / Onboarding | **部分実装** | HOME自動生成はDecision 027で明示的に対象外（Design System着手後に扱うとされていた＝008完了後の**今が該当タイミング**）。Core完全削除UIは未着手のまま |
| 008 | Design System / Theme表示基盤 | **部分実装** | CASE/RESULTS/VOICE・ACCESS固有情報・CTA固有データはDecision 028で明示的に008対象外。Services HOME Teaserの完全自己非表示は新規Block未承認のため簡易実装のまま |

**「COMPLETE報告済みだが持越し事項がある」件数：4/8（005除く006・007・008、および004由来でDecision 028まで持ち越されていた0件表示統一）。** いずれも各Construction自身の報告書・Decisionに明記されており、記録漏れではない。

---

## 3. 全持越し事項の回収

ユーザー提示の候補リストと、本書で新たに発見した事項を統合する。

| # | 項目 | 出典 | 現在の状態 |
|---|---|---|---|
| 1 | Services HOME Teaserの0件時完全非表示 | 008研究§17.3-3 | Archive方式で代替中。`astrea/service-list`相当の新規Blockが必要 |
| 2 | CASE / RESULTS | 02§12、Decision 028 | Core機能未着手 |
| 3 | VOICE | 02§12、Decision 028 | Core機能未着手（掲載許可確認UIを含む） |
| 4 | ACCESS固有情報 | 02§13、Decision 022 | Core機能未着手（住所・営業時間はOffice Profileで既に充足） |
| 5 | CTA / 相談方法 | 02§4/§22の一括列挙、Decision 022 | 電話＋問い合わせページ導線のみ実装。専用データモデル未着手 |
| 6 | HOME最終構成 | 02§6、Decision 027 | **導線が存在しない**（§8-1で詳述、新規発見に近い重要度） |
| 7 | Template / Pattern仕上げ | 02§5 | Header/Footerバリエーション追加、404/Search等の視覚仕上げは最小限 |
| 8 | Responsive総合確認 | 02§23 | Construction単位の断片確認のみ。総合確認は未実施 |
| 9 | 日本語実データ耐久性 | AGENTS.md品質原則 | 未実施 |
| 10 | Accessibility総合監査 | 02§24、Decision 017 | Construction単位の断片確認のみ |
| 11 | Performance | 02§25 | 未実施（自動計測の仕組み自体が無い） |
| 12 | Theme Review | Decision 001 | readme.txt/screenshot.png/LICENSE等が未整備 |
| 13 | WordPress.org互換性 | Decision 001/020 | Version Test Matrixが単一Version（最新+PHP 8.3）のみ |
| 14 | Release packaging | Decision 020 | Build/Package手順が存在しない |
| 15 | Documentation | 05 Baseline§13 | README.md（開発者向け）のみ。ユーザー向け文書0件。CHANGELOG.mdも未作成 |
| 16 | Demo / Screenshot等の公開準備 | — | 未着手 |
| 17 | **（新規発見）GA4基本設定** | 02§18、03ユーザージャーニー | 未実装 |
| 18 | **（新規発見）Core完全削除の確認UI** | Decision 019 | 未実装（uninstall.phpのコメントが「将来のConstruction Order」を明記したまま放置） |
| 19 | **（新規発見）Navigation自動構築とFREE/PRO境界の整合確認** | 02§31、AGENTS.md§6、Construction 007 | 要確認事項として§18-1で報告（修正はしない） |

---

## 4. Release Blocking分類（A〜E）

### A. FREE v1 Release Blocking

| 項目 | 理由 |
|---|---|
| **HOME最終組み立て導線** | 02§32の完成条件「専門家がFREEだけを使って、本番公開できる品質のサイトを、自分自身で構築できること」を、現状のTheme単体では満たせない。静的Front Pageが未設定の新規サイトは、記事0件のBlog Index（`home.html`）がそのままHOMEとして表示され、Hero/Services/CTA等のPatternに一切触れることなく「公開」できてしまう。Setup（007）とDesign System（008）の橋渡しが欠落している。 |
| **GA4基本設定** | 02§18が「管理画面から簡単に設定可能」と明記し、03ユーザージャーニーの正式ステップ22番目にも独立して記載されている。Decision 009はSearch Console領域を限定しただけで、GA4測定ID入力自体を除外していない。実装コストは既存のSearch Console verification欄と同型（テキスト入力＋条件付きscript出力）でSmall。 |
| **Core完全削除の確認UI** | Decision 019が「完全削除はユーザーの明示操作と確認を必要とする」と明記した正式仕様。現状`uninstall.php`は恒久的に何も削除しない（Plugin削除＝データ保持のみ）ため、ユーザーが本当にASTREA Coreの全データを消したい場合の手段が製品内に存在しない。 |
| **CASE / RESULTS / VOICE のCore実装（基本データ層）** | 02§12の文体はService（§7・実装済）やFAQ（§11・実装済）と同格で「0件でも成立する」旨を述べており、個別コンテンツの任意性であって機能自体の任意性ではないと判断する（詳細理由は§5）。 |
| **日本語実データ耐久性の最低限確認** | 02仕様書「Design System」章の「1件でも10件でも、それぞれ適切に成立させる」という明文要件を検証する唯一の手段であり、Release後の見た目破綻を防ぐための最終防波堤。 |
| **Responsive / Accessibility / Performanceの最終手動確認（自動化までは不要）** | 02§32が完成条件として明示。個別Construction単位の断片確認は既に実施済みだが、統合後の通し確認は未実施。 |
| **Packaging（配布用Build手順）** | Release自体が物理的に不可能なため。 |

### B. FREE v1 Recommended

| 項目 | 理由 |
|---|---|
| Services HOME Teaserの完全自己非表示（新規`astrea/service-list`） | 見た目の一貫性の問題であり、現状のArchive方式代替でも「破綻」はしない。 |
| Accessibility/PerformanceのCI自動化（axe-core、Lighthouse CI等） | 02§30が「自動検査・CIへ組み込む」と方向性を示すが、手動確認でも完成条件は満たせる。 |
| readme.txt / screenshot.png / LICENSEファイル整備 | WordPress.org提出をRelease Blockingにしない場合でも、配布物の体裁として安価に対応できる。 |
| ユーザー向けDocumentation（最低限のセットアップガイド） | Setup機能（007）のチェックリストが多くを代替しているため必須ではないが、あった方がよい。 |
| WordPress / PHP Version Test Matrixの拡充（最低Version自体もCIで検証） | 現状は最新Versionのみの実質検証。 |

### C. Post v1

| 項目 | 理由 |
|---|---|
| ACCESS固有情報（最寄駅・徒歩時間・駐車場・地図選択） | Decision 022は責任分離を確定したのみで、データモデル自体は「未実装」と明記されており、新規Core設計が必要。既存Office Profile（住所・営業時間）だけでも最低限のAccessページは今日でも構築可能。 |
| CTA固有データモデル（相談方法の複数管理等） | 現状の電話＋問い合わせページ導線で02仕様書の最低要件は満たせる。より高度なCTA編成はPattern編集で当面代替可能。 |
| VOICE掲載許可確認の専用UI | CASE/RESULTSの基本データ層をv1に含める場合でも、VOICE特有の同意管理UIは複雑さに見合わないため分離できる。 |
| WordPress.org正式提出プロセス一式 | 提出可否はProject-ifの事業判断であり、技術的な提出準備（B）と提出行為自体は分離できる。 |

### D. PRO

| 項目 | 理由 |
|---|---|
| 職種別Navigation自動構築・サイト構成自動生成・デザイン自動設定 | AGENTS.md§6・02§31に明記されたProfession PROの担当領域。 |
| 大規模自動処理前Backup/Restore | 同上。 |
| CASE/RESULTS/VOICEの職種固有内容生成 | 同上（データ層はFREE、内容の自動生成はPRO）。 |

### E. Reject / Not Needed

| 項目 | 理由 |
|---|---|
| GA4 API連携・自動レポート、Search Console API・順位分析 | Decision 009で明示的に除外済み。 |
| 予約管理・高度なCRM化 | 02§31・Decision 006で明示的に除外済み。 |
| 外部Chat/CAPTCHA必須化 | Decision 007の「外部CAPTCHA必須にしない」方針に反する。 |
| 独自Page Builder UI | AGENTS.mdの独自Framework禁止原則に反する。 |

---

## 5. CASE / RESULTS / VOICE の判断

02仕様書§12を再確認した。

> CASE＝対応事例、RESULTS＝公開可能な実績・数字、VOICE＝お客様の声。すべて任意。開業初日で0件でもサイトデザインが成立する。CASEはService等との関連付けを可能とする。VOICEについては掲載許可確認を支援するUIを検討する。

この文体は、Service（§7）・FAQ（§11）と**同じ構造**（「機能としては提供するが、個々のコンテンツ登録は任意」）であり、「機能そのものを実装しなくてよい」とは書かれていない。Service/Price/FAQがConstruction 004で正式に実装対象となった以上、CASE/RESULTS/VOICEも本来同格の基本機能候補であったと判断する。「あると格好いい」という理由だけでBlockingにしているのではなく、**既存の類似機能（Service/FAQ）との一貫性、および02仕様書の文体上の同格性**を根拠とする。

推奨：

- **CASE・RESULTS：基本データ層（CPTまたは共通の投稿タイプ、0..N、Service関連付け）をFREE v1 Release Blockingに含める。** Service/FAQと同型の実装（Construction 004のパターン踏襲）であり、規模はMedium。
- **VOICE：基本データ層（お客様の声テキスト＋任意の属性）はRelease Blockingに含めるが、「掲載許可確認を支援する専用UI」は複雑さに見合わないためPost v1へ送る。** 最低限、投稿ステータス（下書き/公開）による人間の確認フローで02仕様書の最低要求は満たせる。

Coreデータ化が必要か、静的Patternで十分かについては、Service/Price/FAQとの一貫性、および「複数ページから再利用する」という性質（HOME・専用一覧・Serviceページ等）から、**Core CPTとしてのデータ化が必要**と判断する。静的Patternでは複数箇所への再利用・関連付けができない。

---

## 6. ACCESS の判断

現状、Office Profileには住所・電話・営業時間・臨時休業が実装済みである（Construction 002、Decision 022で正本と確定）。一方、最寄駅・徒歩時間・駐車場・地図表示方式はDecision 022で「ACCESSの責任（未実装）」と明記されたまま、対応するCore機能が存在しない。

02仕様書§13を確認：

> Coreに登録された住所、営業時間、最寄駅、徒歩時間、駐車場、臨時休業等を再利用する。Accessページで同じ情報を再入力させない。地図は、ページ内表示／Google Mapsで開く／地図なし等から選択可能にする方向。外部地図サービスへの過剰依存を避ける。

判断：

- **Office Profile既存情報（住所・電話・営業時間）だけで、最低限のAccessページは今日のASTREA FREE v1でも構築可能**（既存のBlock Bindingsをテーマ側で並べるだけ）。これはRelease Blockingではなく、既に成立している。
- **最寄駅・徒歩時間・駐車場・地図選択は、新規Core機能の設計が必要**（データモデル自体が存在しない）ため、**Post v1へ送ることを推奨する**。「地図は…選択可能にする方向」という原文自体が確定仕様ではなく検討中の言い回しであり、Release Blockingとして急ぐ根拠が弱い。
- **Google Maps等の外部APIは、本Audit中は一切導入を検討していない**（指示通り）。Post v1で地図機能を設計する際も、外部APIへの過剰依存を避けるという02仕様書の原文方針を継承すべきと申し送る。

---

## 7. CTA / 相談方法 の判断

現状実装：Office Profile電話番号のBlock Bindings（`phone`/`phone_tel`）、Header・HOME CTAでの電話ボタン、Contact Formへの導線（Construction 007のSetup生成ページ、Construction 008のCTA Pattern）。

正式仕様上の言及：02§4の一括列挙（「相談方法、CTA」）、Decision 022の「CTA・相談方法はOffice Profile / Professional Profileのいずれにも含めず、別責任（CTA / Consultation）として扱う」。Decision 022はCTAを**分離すると決めただけ**で、具体的なデータモデルや必須項目を定義していない。03ユーザージャーニーには「CTA設定」という独立ステップが存在する。

判断：

- 電話・問い合わせページへの導線という**現状実装で02仕様書の字面上の最低要件（相談まで迷わないこと、00仕様書の専門家サイト要件）は満たせる**と判断する。
- 「CTA設定」という独立したユーザージャーニーステップに対応する具体的な管理画面は現状存在しないが、これはHOME CTA Pattern自体をSite Editorで編集する（ボタンのリンク先を問い合わせページへ張り替える等）操作で代替可能であり、**新しいCRM・予約システム・外部チャットは指示通り追加しない**。
- **不足していると判断する項目はない。** CTA専用データモデル（相談方法の複数管理、優先順位付け等）はPost v1（§4-C）とする。

---

## 8. Theme表示完成度

### 8-1. HOME（最重要の発見）

**現状、ASTREAを有効化しただけの新規サイトのHOMEは、記事0件のBlog Index（`home.html`）が表示される。** 静的Front Pageを設定する導線・Home用Patternをまとめて配置する導線のいずれも製品内に存在しない。008で作成したHero/Trust/Services Teaser/Professional Teaser/Price/FAQ/CTA/Flowの8 Patternは、**ユーザーが自力でSite EditorからPageを新規作成し、Front Page設定を変更し、8個のPatternを手動で挿入して初めて使われる**。これはConstruction 007（Decision 027「ホームは対象外とし、Design System着手時に改めて扱う」）と008（Home Patternのみ提供、組み立てはユーザー任せ）の両方で意図的に先送りされてきたが、**008が完了した今、先送りする理由が無くなっている**。

02§32の完成条件（「専門家がFREEだけを使って、本番公開できる品質のサイトを、自分自身で構築できること」）と、02§21「ページを自動生成するのではなく、ユーザーへ『次に何をすればいいか』を案内する」の両立を考えると、**Decision 016と同型の「明示的なユーザー操作によるHOME組み立て支援」をConstruction 007のSetup機能へ追加する**のが最も一貫した解決策と考えられる（詳細は§19の009案）。

### 8-2. その他のTemplate

| Template | 状態 | 所見 |
|---|---|---|
| Blog（`home.html`） | 実装済 | 0件時`query-no-results`あり |
| Page（`page.html`） | 実装済 | 最小限（見出し＋本文のみ） |
| Single（`single.html`） | 実装済 | 最小限。記事とService/FAQの関連付け（02§19）は未実装 |
| Search（`search.html`） | 実装済 | 0件時メッセージあり |
| 404（`404.html`） | 実装済 | 最小限 |
| Service Archive/Single | 実装済 | Construction 004由来、0件時メッセージ追加済み（008） |
| Professional Archive | 実装済 | Single Templateは無く投稿の`the_content`依存（個別URLの見た目はDesign System未適用の可能性、要目視確認） |
| Price | 実装済（専用ページはユーザーがSetupで生成） | 見た目はDynamic Blockの素のHTMLのみ、Design System上のカード化等の装飾は未実装 |
| FAQ | 実装済 | Archive/Taxonomyのみ。個別FAQ単独ページの体裁は簡素 |
| Contact | 実装済 | フォーム自体はConstruction 005、Design System上の装飾（Card化等）は未適用 |
| Office/About | Setup生成ページ＋プレースホルダー本文のみ | Office Profile情報（住所・営業時間等）を表示するPatternが無い＝About/Accessページを作っても情報が「本文に手で書く」以外の方法で出せない |

**追加の発見：Professional Single（個別プロフィールページ）専用Templateが存在しない。** `single-astrea_professional.html`が無く、`index.html`へフォールバックする。Serviceには`single-astrea_service.html`があるのに、Professional Profileには無いという非対称性がある。

**追加の発見：Office Profile（事務所情報）を表示する専用Patternが無い。** Header/Footerでの断片表示（事務所名・電話・住所）はあるが、「Aboutページ」で住所・営業時間・SNSリンクをまとめて表示するPatternが存在しない。Setup生成の「事務所概要」ページは本文がプレースホルダー文言のみで、Office Profileの構造化データ（営業時間表・SNSリンク一覧）を表示する手段が無い。

---

## 9. 日本語実データ耐久性

未実施と判断する。現在のテスト・smoke-testのフィクスチャは短い固定文字列（「スモーク事務所」等）が中心で、以下のような実務上典型的な長さ・パターンでの確認は行われていない。

- 法人名（「〇〇行政書士法人」等、20文字前後）
- 長い代表者名・複数Professional（5名以上）の同時表示
- 長い資格・所属（改行を含む複数行）
- Service名の長文化、長文説明
- Price自由記述欄の長文
- FAQ回答の長文（Block Editorの通常投稿本文相当）
- 日本の一般的な電話番号・郵便番号付き住所
- Navigation項目が5〜8個に増えた場合の折り返し
- 問い合わせ本文の長文（既存のMESSAGE_MAX_LENGTHは検証済みだが表示側は未確認）

**Desktop/Tablet/Mobileの3幅×上記データパターンでのCard/Grid崩れ確認が必要。** Release Blockingとして提案する（§4）。

---

## 10. Responsive

008のtheme.jsonはFluid Typography（`fontSizes`の`fluid`設定）とLayout（`contentSize`/`wideSize`）を備えており、「PCの縮小」ではなくWordPress標準の流体タイポグラフィ機構を使っている点は仕様に沿う。ただし、以下は本Audit時点で**実機確認できていない**（製品コード変更禁止のため今回は検証のみ許可されている範囲でも、ブラウザ幅変更を伴う視覚確認は本書執筆時点で未実施）。

- Header：Navigation Overlay（WP標準ハンバーガー機構）が実際に320px幅で機能するか
- Hero/CTA：ボタンの折り返し、文字サイズの実際の見え方
- Card系（Services Teaser、Price、FAQ）：Grid/Columnsの折り返し
- Footer：Navigationと事務所情報の並び順が狭幅で破綻しないか
- 長い日本語（分かち書きされない長い単語）の折り返し

Release Blockingの一部として、実機・ブラウザ幅変更での確認を推奨する（§4、§9と合同で実施可能）。

---

## 11. Accessibility Final Audit

構築済みの個別確認（見出し階層、Semantic HTML、Nonce、Form Label等）はConstruction単位で都度実施されているが、**サイト全体を通した統合監査は未実施**。

候補項目のうち、特に未確認と判断するもの：

- Skip Link（現在Header/Footerに実装されていない——WordPress標準Block Themeは`skip-link`をTheme側で明示的に用意する必要があり、008では未対応）
- Landmark構成の全Template横断確認（008で発見・修正したHeader/Footerの二重Landmark以外にも類似の問題が無いか）
- Zoom（200%拡大時のレイアウト崩れ）
- Touch Target（44px以上の推奨サイズ、特にモバイルNavigationのボタン類）
- Reduced Motion（現状Animationをほぼ使っていないため影響は小さいと推測されるが未検証）

WordPress.org Theme Review自体はAccessibility準拠を必須としない（Accessibility Readyタグは任意）が、02§24・Decision 017・00仕様書の製品思想はAccessibilityを標準品質として扱うことを明言しており、**Release Blockingとして最低限の統合監査（Skip Link追加を含む）を推奨する**。

---

## 12. Performance

現状、自動計測の仕組み自体がCIに存在しない（PHPCS/PHPUnit/smoke-testのみ）。Architecture上の明確な懸念点：

- 外部リソース：現状ゼロ（Google Fonts等も使用せずシステムフォントのみ——008のtheme.jsonで確認済み、良好）
- Dynamic Block：`astrea/price-list`・`astrea/faq-list`・`astrea/representative`はいずれもDBクエリを都度実行する設計（キャッシュ無し）。件数が少ないFREE想定では問題にならないと考えられるが、将来的な留意点。
- Query数：HOME全Pattern（Hero+Trust+Services+Professional+Price+FAQ+CTA+Flow）を1ページに全部載せた場合、Query Loop 1件＋Dynamic Block 3件が同一ページで実行される。過大ではないが、Release前に実際のTTFBを一度計測することが望ましい。
- Core非活性時：静的Fallbackのみで追加クエリなし、良好。
- CSS/JS：Theme独自のCSS/JSファイルはゼロ（theme.jsonのみ）。WordPress標準の自動生成CSSに依存しており、独自資産の肥大化リスクは低い。

**Lighthouse点数を目的化した最適化は不要と判断する。** 明確なArchitecture上の問題は見つからなかったため、Performanceは「Release前に一度実測して極端な異常が無いことを確認する」程度をRecommended（B）とする。

---

## 13. WordPress / PHP Compatibility

### 現在の正式仕様（Decision 020）

- PHP：8.3以上（1.0初期最低ライン）
- WordPress：7.0以上を初期Target、7.1を主開発・Test基準

### 現状のCI実態

`.github/workflows/ci.yml`は、PHP 8.3固定・wp-envのデフォルト（`"core": null` = 最新安定版）のみでテストしている。**最低対応Version（WordPress 7.0、あるいはPHP 8.3ちょうど）自体を検証するTest Matrixは存在しない。**

### 提案するTest Matrix（Release前に必要）

| 軸 | 現在 | 提案 |
|---|---|---|
| PHP | 8.3のみ | 8.3（最低）＋ 最新安定版（例：8.4/8.5系）の2点 |
| WordPress | 最新のみ | 7.0（最低保証）＋ 最新安定版の2点 |
| Block Theme前提 | 該当 | 変更なし（Classic Theme互換は仕様上不要） |
| Core Plugin有無 | 4状態確認済み（Decision 021） | 変更なし、継続 |
| Child Theme | 未検証 | Decision 011は「妨げない」水準の要求のみ。標準Child Theme作成手順で1回動作確認する程度で十分 |

**既存Decision（020）のVersion方針そのものを変更する必要は無いと判断する。** 変更が必要になるとすれば「WordPress 7.0での動作実績が無い」という技術的発見があった場合のみであり、現時点でそのような兆候は無い。念のため要確認事項とはしない（Decision変更提案ではなく、CI Matrix拡充という実装作業の提案に留まる）。

---

## 14. WordPress.org Theme Review readiness

| 項目 | 状態 |
|---|---|
| Theme headers（`style.css`） | 整備済み（Version/Requires/License等） |
| Licensing / GPL compatibility | GPL v2 or laterを明記。バンドルフォント無し（システムフォントのみ）でライセンス懸念は無い |
| Escaping / Sanitization | 各Constructionで継続的に確認済み、PHPCS（WPCS）でも継続監視 |
| Translation / Text Domain | `astrea`/`astrea-core`で一貫。ただし`languages/`ディレクトリは`.gitkeep`のみで実際の`.pot`ファイルが無い |
| Theme Check相当ツール | 未実行（Theme Check PluginまたはTheme Sniffer相当を一度も通していない） |
| Plugin territory | Core機能をTheme側へ実装していないため、Plugin Territory違反の兆候は無い（Decision 002/013の責任分離を継続的に遵守） |
| Admin notice | Core推奨Notice（Construction 007）はDismiss可能・非強制で、Theme Review基準に沿う設計 |
| External requests | ゼロ（確認済み） |
| Privacy | Contact機能のPrivacy配慮（Decision 003-007）は実装済み。Privacy Policy自体はWordPress標準機能への案内のみ（Decision 016で明記） |
| **screenshot.png** | **未作成**（WordPress.org必須） |
| **readme.txt（Theme/Core双方）** | **未作成**（WordPress.org必須フォーマット） |
| **LICENSE ファイル** | **未作成**（リポジトリルート） |

**WordPress.orgへの実提出自体をFREE v1 Release Blockingにするかどうかは、Project-ifの事業判断（配布戦略、Decision 001の「積極的に目指す」がv1と同時か1.1以降かの選択）であり、本Audit範囲では判断しない。** ただし、readme.txt/screenshot.png/LICENSEの整備自体は提出可否によらず低コストであるため、Recommended（B）として提案する。

---

## 15. Core Plugin Release品質

| 項目 | 状態 |
|---|---|
| Plugin headers | 整備済み（Version/Requires/License等） |
| Activation / Deactivation | 実装済み・Test済み（CPT登録、Rewrite Flush、Cron scheduling/clearing） |
| **Uninstall（完全削除UI）** | **未実装**（§4のRelease Blocking項目、Decision 019） |
| Migration | Office ProfileのSchema v1→v2実装済み。他機能は初版のためMigration不要 |
| Capabilities | 各機能で`manage_options`/`edit_post`等を一貫使用、Test済み |
| Nonce | 全Formアクションで実装・Test済み |
| Cron | Contact Retention/Digest通知で実装・Test済み、Core非活性時のCatch-up含む |
| Contact retention | 実装・Test済み（10/30/60/90日、既定30日） |
| Security | 各Constructionで実HTTP検証済み（XSS、CSRF、Token Replay等） |
| Privacy | Contact本文・Tokenのログ非記録を確認済み |
| Translation | Text Domain一貫、`.pot`ファイル自体は未生成 |
| DB残存方針 | Decision 019どおり、Deactivate/Uninstallいずれもデータ保持がデフォルト（意図通り） |
| **Theme無しでもFatalしないか** | 未確認——ASTREA Coreは現状「ASTREA Theme前提」で設計されていないため理論上問題は無いはずだが、他Themeを有効化した状態での実HTTP確認は本Audit範囲では未実施 |

---

## 16. Packaging

**配布用ZIP（`astrea.zip`/`astrea-core.zip`）の生成手順は現状存在しない。** `tools/`にはCI用の`smoke-test.sh`しか無く、Build/Packageスクリプトが無い。

現在のディレクトリ構成（`theme/`＝Theme本体そのもの、`core/`＝Plugin本体そのもの）は、`tests/`・`node_modules/`・`vendor/`（開発用Composer依存）・`docs/`・CI設定等と分離されており、**配布物に混入させないための土台自体は既に整っている**（`theme/`配下と`core/`配下だけを固めてZIP化すればよい構造）。ただし、それを実行する再現可能なBuildスクリプト（`npm run build`相当）は未作成であり、Release Blockingとして必要と判断する。

---

## 17. Documentation

現状：README.md（開発者・Repository向け）、HISTORY.md/csv（内部履歴）、AGENTS.md（開発体制）のみ。**エンドユーザー向け文書はゼロ。**

分離方針の提案：

| 内容 | 置き場所 |
|---|---|
| Install手順、Coreは任意・公式推奨、初期Setup、Office/Professional/Service/Price/FAQ/Contact/SEOの使い方、Style Variationの選び方 | **Project-ifサイト側**（頻繁に更新される可能性が高く、スクリーンショット等リッチコンテンツに向く） |
| 最低限のreadme.txt（WordPress.org形式の簡潔な説明・インストール手順） | **Repository/Package内**（Theme Review提出時の必須要件） |
| Support policy、Privacy（製品としての方針） | **Project-ifサイト側**（Decision 015のサポート原則に対応） |
| CHANGELOG.md（ユーザー向け、05 Baseline§13で言及されているが未作成） | **Repository内**（HISTORY.mdとは別に新設が必要） |

Setup機能（Construction 007）の管理画面内チェックリストが多くの操作案内を代替しているため、**大量の外部ドキュメントをRelease Blockingにする必要は無い**と判断する。ただし、CHANGELOG.mdの新設とreadme.txtの最低限の整備はRecommended（B）とする。

---

## 18. Release Candidate方針

現在の開発規模（実質1名体制、Construction単位でのDecision駆動開発）を踏まえ、過剰な工程を避けた**2段階**を提案する。

- **RC1：全機能統合試験。** §9-12の総合監査（日本語実データ、Responsive、Accessibility、Performance）、HOME最終導線、GA4、Core完全削除UIを含めた全機能を対象に、smoke-test.sh拡張＋手動横断確認を実施する。
- **v1.0 Release：** RC1で発見された不具合の修正確認後、Packaging（§16）を経て正式Release。

RC2（追加の修正確認ラウンド）は、RC1で重大な不具合が発見された場合にのみ追加する可変ステップとし、あらかじめ固定工程として計画しない（過剰な工程を増やさないという指示に従う）。

---

## 18-1. 要確認事項（重要：FREE/PRO境界の疑義）

**発見事項：** 02仕様書§31「ASTREA FREE v1でやらないこと」および AGENTS.md §6「Profession PROの責任分界」の両方に、**「Navigation自動構築」がProfession PROの担当領域として明記されている**（05 Baselineの表にも同内容の記載あり）。

一方、Construction Order 007は「基本メニューを作成する」というNavigation自動生成機能（`core/includes/setup-navigation.php`）を**FREE v1のCore機能として実装済み**であり、既にCI Greenで完了報告されている。

**両者の関係について、本Audit時点では以下のいずれとも解釈可能であり、断定していない。**

- 解釈A：AGENTS.md/02§31の「Navigation自動構築」は、Profession PROが「職種固有知識を用いて」自動構築する高度な機能を指し、Construction 007の「明示的なユーザー操作による、職種非依存の汎用リンク集約」とは性質が異なるため、矛盾しない。
- 解釈B：文言上「Navigation自動構築」という機能カテゴリ自体がPRO専用と読め、たとえユーザー操作を挟むFREE版の簡易実装であっても、02仕様書上は越境している。

03ユーザージャーニーには「メニューを構築」という独立したFREEステップが存在し、これは解釈Aを支持する材料である。しかし、これは断定材料ではなく状況証拠に留まる。

**本Auditでは、Construction 007の実装を変更・撤回せず、この疑義を報告するに留める。** 009着工前にユーザー判断を要する事項として、最終報告§7で改めて提示する。

---

## 19. CONSTRUCTION 009〜Release工程表

| # | 名称 | 目的 | 施工範囲 | Blocking | 依存 | 規模 |
|---|---|---|---|---|---|---|
| **009** | HOME組み立て支援 / GA4 / Core完全削除UI | Setup（007）とDesign System（008）の橋渡し、および見落とし2件の解消 | Setup機能へ「HOMEをPatternで組み立てる」導線追加（Front Page自動設定＋8 Pattern一括挿入、Decision 016型の明示操作）／SEO設定画面へGA4測定ID欄＋条件付き出力追加／Core管理画面へ「全データ削除」確認フロー追加（Decision 019） | **Blocking** | 007・008完了 | Medium |
| **010** | CASE / RESULTS / VOICE | 02§12の基本データ層をFREEへ実装 | 新規CPT（Service/FAQと同型パターン）、HOME/専用一覧への表示、Service関連付け。VOICE同意UIはPost v1として簡易化 | **Blocking**（§5参照） | 004のパターン踏襲、008のTeaser設計踏襲 | Medium〜Large |
| **011** | Theme表示補完 | §8で発見した表示上の穴を埋める | `single-astrea_professional.html`新設、Office Profile情報表示Pattern（About/Access用）新設、Price/FAQ/Contactの視覚的仕上げ、Skip Link追加 | **Blocking**（一部はRecommendedへ格下げ可） | 008完了 | Medium |
| **012** | 総合監査（Responsive / Accessibility / Performance / 日本語実データ） | Release前の最終品質確認 | §9-12の全項目を実機・ブラウザ幅変更・実データで確認。発見した不具合はConstruction 013で修正 | **Blocking** | 009-011完了 | Medium |
| **013** | 監査対応 / Bug Fix | 012で発見した不具合の修正 | 012の結果次第で確定 | **Blocking** | 012 | 012依存で可変 |
| **014** | Release準備（Packaging / Documentation / WordPress.org readiness） | 配布可能な状態を作る | Build/Packageスクリプト、CHANGELOG.md、readme.txt、screenshot.png、LICENSE、CI Version Matrix拡充 | **Blocking**（WordPress.org実提出自体は別途判断） | 013完了 | Medium |
| **RC1** | Release Candidate | 統合試験 | smoke-test拡張＋手動横断確認、最終Bug潰し | Blocking工程扱い | 014完了 | Small〜Medium |
| **v1.0** | Release | 正式リリース | Packaging済み成果物の配布開始 | — | RC1完了 | Small |

**Post v1バックログ**（工程番号を振らず、1.1以降で個別Construction化）：ACCESS固有情報、CTA専用データモデル、Services HOME Teaserの完全自己非表示Block、VOICE同意UI高度化、WordPress.org正式提出、Accessibility/Performance CI自動化、拡充ユーザードキュメント。

工程を細分化しすぎないよう、Theme表示補完（011）は008の続きとして、監査対応（013）は012の結果が出るまで独立工程化しないという判断も可能である（012の結果が軽微であれば012内で即修正し013を省略してよい）。

---

## 20. 完成率再評価

単純なConstruction数（8/14 ≈ 57%）ではなく、以下の重み付けで評価する。

| 観点 | 完成度 | 根拠 |
|---|---|---|
| 機能完成度（Core機能） | 約80% | Office/Professional/Service/Price/FAQ/Contact/SEO/Setupは実装済み。CASE/RESULTS/VOICE・ACCESS固有・CTA固有が未着手 |
| Theme完成度 | 約65% | Design System基盤・Style Variation・主要Templateは実装済みだが、HOME組み立て導線・Professional Single・Office表示Patternが欠落 |
| Release品質（総合監査） | 約20% | Construction単位の断片確認のみ、統合監査は未着手 |
| Documentation | 約15% | 開発者向けのみ、ユーザー向けはゼロ |
| Packaging | 0% | 未着手 |

**加重平均（機能40%・Theme25%・Release品質15%・Documentation10%・Packaging10%）でおよそ55-60%と評価する。** これはProject Management上の概算値であり、厳密な工数見積りではない。

---

## 21. HISTORY記録

本Auditも1工程としてHISTORY.csvへ記録する（Who=Chloe、Start/End/Durationは実測、Order="REMAINING WORK AUDIT"）。詳細は本文書末尾のコミット情報を参照。

---

## 付録：要確認事項一覧（再掲）

1. **§18-1：Navigation自動構築とFREE/PRO境界の整合。** Construction 007の実装を維持するか、境界の再定義（新規Decision）が必要か、着工前にユーザー判断を要する。
2. **§5：CASE/RESULTS/VOICEをRelease Blockingとする判断への同意確認。** 「あると格好いい」ではなく02仕様書の文体上の同格性を根拠としているが、最終判断はユーザーに委ねる。
3. **§6：ACCESS固有情報をPost v1とする判断への同意確認。**
4. **§14：WordPress.org実提出のタイミング（v1同時 or 1.1以降）。**
5. **§18：RC1のみとするか、RC2まで見込むか（現時点ではRC1のみを推奨）。**

以上、いずれも本Auditでは独自に確定させず、次の正式着工命令の前提として提示する。
