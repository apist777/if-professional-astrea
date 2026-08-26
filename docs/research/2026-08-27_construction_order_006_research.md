# CONSTRUCTION ORDER 006 — SEO Foundation 着工前調査・施工計画

- 実施日: 2026-08-27（JST）
- 担当: クロエ（Claude）
- 対象: SEO Foundation（title/meta description/canonical/robots/OGP/Breadcrumb/構造化データ/Search Console）の設計
- 性質: **調査・設計のみ。製品コード変更なし。**
- 基準文書: `05_astrea_free_v1_construction_baseline.md`、Decision 001〜025、`02_astrea_free_v1_specification.md` §16-18、`04_astrea_free_v1_preconstruction_decisions.md` Decision 008/009/010/018、Construction Order 004報告書・研究資料
- Web一次情報の確認日: 2026-08-27（JST）。各項目に出典を明記する。

---

## 1. 現行正式仕様の完全抽出

### 1.1 02仕様書 §16 SEO Foundation

> ASTREA FREE単体で、基本的な本番SEO運用が可能な状態を目標とする。別SEO Pluginを必須としない。
> 対象候補は、**title、meta description、canonical、robots、OGP、Breadcrumb、基本構造化データ、Search Console認証、XML Sitemap** 等。
> Sitemapは自動管理を基本とする。
> WordPress標準機能で適切に実現できるものは標準を活用し、不必要な独自実装を避ける。
> SEOを知らないユーザーでも基本的に妥当な状態になるよう自動判断し、詳細設定は上級者向けとする。SEO設定で100点を追わせず、短時間で必要十分な状態にして集客活動へ進ませることを優先する（Decision 008）。
> 他SEO Pluginを利用したいユーザーについては二重出力を避け、邪魔をしない。この方針はSEO Plugin以外の主要な第三者Pluginとの競合可能性にも一般化し、既知の競合事例が判明したものをUpdateで検出リストへ追加できる運用とする。未知Pluginを推測で制御しない（Decision 018）。
> 【Decision 010】Breadcrumbは、視覚的なBreadcrumb UI（Theme側）と、検索エンジン向けのBreadcrumbList構造化データ（Core側でJSON-LD出力）の両方を標準対応する。WordPress標準の投稿階層・分類（Taxonomy）を利用し、Breadcrumb専用の独自データ正本は作らない。過剰なユーザー設定は要求しない。

### 1.2 02仕様書 §17 OGP

> サイト標準OGP画像を設定可能とする。ページ個別画像等が存在する場合のFallback順序を定義する。画像アップロード画面では、その時点で推奨される画像サイズ・比率等を必ずその場で案内する。推奨サイズから大きく外れる画像には穏やかな警告を表示できるよう検討する。SEOスコア等でユーザーを採点するUIにはしない。

### 1.3 02仕様書 §18 GA4 / Search Console

> 管理画面から簡単に設定可能とする。ユーザー自身にHTMLやJavaScript編集を要求しない。使わない場合は関連処理を読み込まない。
> 【Decision 009】Search Consoleの実装範囲はHTMLタグ方式による所有権確認の支援に限定する。**Search Console API、OAuth連携、検索順位分析等は作らない。** GA4による所有権確認が可能な場合は案内してよいが前提にはしない。WordPress標準のXML Sitemap URLとSearch Consoleへの登録手順を案内する。

### 1.4 「やらない」と明文で決まっている事項

- SEOスコアゲーム（02§16, §31、AGENTS.md §13）
- FAQ・地域ページ等のSEO目的大量生成（02§31、AGENTS.md §13、02§11「FAQはSEOのための量産装置にはしない」）
- Search Console API・OAuth連携・検索順位分析（Decision 009）
- 他SEO Pluginとの二重出力・妨害（Decision 008/018）
- ユーザーへのSEO採点UI（02§17）

### 1.5 05 Baselineが実装フェーズへ委ねている事項（セクション17）

> 3. PRICE（自由記述）と構造化データ（schema.org Offer等）の整合方法。

Construction Order 004で「Priceは自由記述を維持し、構造化データ出力（Offer等）は本工程の対象外」と暫定判断済み。FAQPage構造化データについても同工程で「本工程では意味データのみ、構造化データ出力の要否はSEO Foundation工程へ引き継ぐ」と明記されている。本書がその引き継ぎへの正式な回答を行う。

### 1.6 Accessibility / Privacyとの関係（明文の直接規定はないが既存原則から導出）

- Accessibility：Breadcrumb視覚UIは`nav[aria-label]`等のセマンティックHTMLで実装（既存の見出し階層・Semantic HTML品質基準、Decision 017と整合）。JSON-LDはユーザーに見えない`<script type="application/ld+json">`であり、Accessibility要件は視覚的Breadcrumb・OGP由来の画像alt等、目に見える部分にのみ適用される。
- Privacy：SEO出力はすべて**公開ページの`<head>`**に出るため、Contact（問い合わせ内容・氏名・メール等の非公開個人データ）を絶対に混入させないこと（§11で後述）。

---

## 2. WordPress標準機能との責任境界（2026年8月時点の一次情報）

WordPress Coreは長年かけてSEOの基礎部分を継続的に取り込んでおり、ASTREAが重複実装すべきでない領域は年々広がっている。

| 項目 | WordPress標準の提供状況 | ASTREAの対応方針 |
|---|---|---|
| `<title>` | `add_theme_support('title-tag')` + `wp_get_document_title()` がCore標準（`wp_title`は非推奨）。Block Themeは通常これを既定で有効化する | Theme側で`title-tag`サポートを確認するのみ。独自Title生成ロジックは書かない |
| canonical | `wp_get_canonical_url()` + `rel_canonical()`（`wp_head`にCore標準で登録済み）、`get_canonical_url`フィルタで調整可能 | WordPress標準の出力をそのまま使う。ASTREA独自でcanonicalタグを`echo`しない |
| robots meta | `wp_robots()`（`wp_head`にCore標準登録、`wp_robots`フィルタで調整、WP 5.7〜） | 個別投稿の「検索エンジンにインデックスさせない」等の設定が必要な場合、`wp_robots`フィルタへ値を追加する方式を採用し、独自の`<meta name="robots">`出力コードを書かない |
| XML Sitemap | `/wp-sitemap.xml`がCore標準（WP 5.5〜）。対象投稿タイプ・Taxonomy・著者アーカイブ・トップページを自動収録 | Sitemapを自前生成しない。Search Console案内で「WordPress標準のSitemap URL」として案内する（Decision 009の明文どおり） |
| Site Icon（favicon） | Core標準（WP 4.3〜、`wp-admin`のカスタマイザー） | 独自Favicon設定UIを作らない。OGP fallback画像の選択肢としては別扱い（favicon用途とOGP用途は解像度要件が異なるため流用しない） |
| Featured Image | Core標準 | OGP画像のFallback順位の一部として参照する（後述§9） |
| meta description | **WordPress Coreに存在しない** | ASTREA Coreが担当する（§16の明文どおり） |
| OGP (`og:*`) | **WordPress Coreに存在しない** | ASTREA Coreが担当する |
| 構造化データ (JSON-LD) | **WordPress Coreに存在しない**（投稿階層等の「元データ」はCoreにあるが、JSON-LD出力機構はない） | ASTREAが必要な範囲のみ出力する（§4以降で選別） |
| Search Console verification meta | WordPress Coreに存在しない | ASTREA Coreが担当する（Decision 009の範囲内） |

出典：
- [`wp_get_canonical_url()` – WordPress Developer Resources](https://developer.wordpress.org/reference/functions/wp_get_canonical_url/)
- [`get_canonical_url` フィルタ – WordPress Developer Resources](https://developer.wordpress.org/reference/hooks/get_canonical_url/)
- [`wp_robots()` – WordPress Developer Resources](https://developer.wordpress.org/reference/functions/wp_robots/)
- [WordPress 5.5 sitemaps機能 – Make WordPress Core](https://make.wordpress.org/core/2020/07/22/new-xml-sitemaps-functionality-in-wordpress-5-5/)
- [`wp-sitemap.xml`解説 – Elementor Blog](https://elementor.com/blog/wordpress-sitemaps-explained/)（Core標準機能の限界＝メタディスクリプション/構造化データはCore範囲外という一致した記述）

**結論**：ASTREAが独自実装すべきは「meta description」「OGP」「構造化データ（選別済みの範囲）」「Search Console verification meta」のみ。title・canonical・robots・Sitemap・Faviconは既存のWordPress標準フィルタ/機構へ薄く連携するだけで足りる。02仕様書§16の対象候補リストのうち約半分はすでにWordPress自身が満たしている。

---

## 3. SEO Plugin共存戦略

Decision 018が既にPlugin共存の一般原則（既知Plugin検出・Update時追加式リスト・未知Pluginは推測制御しない）を確定している。SEO Foundationはこの原則をそのまま適用すればよく、SEO Plugin専用の新しい方針を作る必要はない。

### 推奨Architecture

1. **標準Hook経由の連携を優先する**：ASTREAが担当するmeta description/OGP/JSON-LDは、すべて`wp_head`への直接`echo`ではなく、`wp_robots`フィルタのような「WordPress標準の合成ポイント」がある部分は必ずそれを使う（robots meta等）。meta description/OGP/JSON-LDにはWordPress標準の合成フィルタが存在しないため、ASTREAが`wp_head`へ独自に追加する（他プラグインと同様の一般的な実装形態であり、これ自体は特別な対応ではない）。
2. **機能検出による自動停止（無限個別対応をしない）**：個別Plugin名を無数に列挙する互換表は作らない。代わりに、**「そのPluginがすでに同じ種類のmeta/JSON-LDを出力しているかどうか」を汎用的に検出する**方式を検討する。具体的には：
   - 主要SEO Plugin（Yoast SEO、All in One SEO、Rank Math、SEOPress等、日本語圏で高シェアのもの）は、いずれも自身の存在を`defined()`定数や既知のクラス名で検出可能（例：`class_exists('WPSEO_Options')`等）。この**「既知シグネチャによる検出」自体は既にDecision 018が想定する「既知Plugin検出」の一種**であり、新しい方針ではなく既存方針の適用である。
   - 検出できた場合、ASTREA側の該当出力（meta description / OGP / 該当JSON-LD）を機能単位で自動停止する。停止は「機能ごと」に行い、Plugin側が出していない機能（例：Breadcrumb JSON-LDだけ出していない場合）はASTREA側が補完してよい、という粒度にする。
   - 検出できない未知Pluginについては、Decision 018どおり推測制御しない。二重出力が発生する可能性はあるが、これは「未知Pluginとの組み合わせ動作を保証しない」という既存の第三者Plugin共存方針の範囲内であり、SEO Foundation固有の新しいリスクではない。
3. **管理画面での注意喚起**：ASTREA自身の出力を停止した場合、管理画面（Contact/Inquiry管理画面と同様の場所、または新設のSEO設定画面）に「既知のSEO Pluginを検出したため、該当機能はそちらに委任しています」という非侵襲的な通知を表示する（Office Profileの代表者移行通知と同種の、状態に応じて自動的に出現/消滅する通知パターンを踏襲）。
4. **巨大な互換表を作らない**：既知シグネチャのリストは実装上のコードとして数個程度の主要Pluginに限定し、正式仕様の追加変更なしにUpdateで追加できる設計とする（Decision 018がすでに明記している運用方針の再確認）。

この設計はDecision 018の外挿であり、新しいDecisionを必要としない。

---

## 4. Meaning Data → Structured Data：総論

Core既存の意味データ（Office Profile / Professional Profile / Service / Price / FAQ）のうち、構造化データへ対応付けるべきものを個別に判断する。「データがあるから全部JSON-LDにする」は明確に禁止されているため、各データについて「①Schema.orgとしての意味が合っているか」「②Google Rich Resultとしての実益があるか」「③誤用リスク」の3点を確認したうえで判断する。

結論を先取りすると：

| データ | 対応するSchema.org型 | 出力可否（FREE v1） |
|---|---|---|
| Office Profile | `Organization`（住所は`PostalAddress`、電話は`telephone`） | **出す**（§7） |
| Professional Profile | `Organization.employee`内の`Person` | **出す**（§7、Officeの一部としてのみ。単独ページの`Person` JSON-LDは対象外） |
| Breadcrumb（投稿階層由来） | `BreadcrumbList` | **出す**（Decision 010で既に確定済み。§8で設計） |
| FAQ | `FAQPage` | **出さない**（§6で詳述。2026年5月にGoogle Rich Result自体が廃止された） |
| Price | `Offer` / `PriceSpecification` | **自動出力しない**（§5で詳述。自由記述との不整合が実害を生むリスクが高い） |

---

## 5. Price / Offer問題（Construction Order 004からの引き継ぎ事項）

### 5.1 技術的事実（一次情報確認済み）

schema.orgの`price`プロパティは「数値、または数値のみからなる文字列」を要求する。通貨記号・カンマ区切り・自由文を含めることは仕様上想定されていない（小数点は`.`を使い`,`を使わない、`$`等の記号を含めない、という具体的な明記あり）。

出典：[`price` – Schema.org Property](https://schema.org/price)、[PriceSpecification Clarifications – W3C Wiki](https://www.w3.org/wiki/WebSchemas/PriceClarifications)

ASTREA Priceの現行データモデル（Construction Order 004で確定）は「固定額／○円〜／月額／時間制／無料／個別見積／自由表記」を単一の自由記述文字列（`astrea_price_amount`）として保持する。「月額5,000円〜」「初回相談無料」「個別見積」等は、いずれもschema.orgが要求する「数値のみの文字列」に機械的に変換できない。

### 5.2 選択肢の比較

| 案 | 内容 | 評価 |
|---|---|---|
| A. Offerを一切自動出力しない | Price意味データはJSON-LD化せず、意味データとしてのみ保持する | **安全。仕様変更不要。誤ったOffer出力によるSearch Consoleエラー・Rich Result不承認のリスクがゼロ** |
| B. 明確に変換可能なPriceのみbest-effort出力 | `astrea_price_amount`が「数字のみ」（例："5000"）等、曖昧さなく数値として解釈できる場合に限りOfferを出力し、それ以外（"月額"や"〜"を含む等）は出力しない | 部分的な実益はあるが、「同じPrice一覧の中で一部だけJSON-LDが付き一部だけ付かない」という一貫性のなさが生まれる。判定ロジックの誤爆（例："0円"を無料と誤認、通貨単位の見落とし）のメンテナンス負担も発生する |
| C. 将来別の構造化入力項目を設ける（例：税込/税別・数値専用フィールドを新設） | Priceに新しい項目を追加し、自由記述とは別に構造化データ専用の数値フィールドを持たせる | **新規フィールド追加＝仕様変更に該当**。指示文により無承認での実装は不可。ユーザーへの追加入力要求も増える（60点公開思想・入力最小化の原則と緊張関係） |
| D. その他 | 検討したが、A/B/C以外に仕様を壊さない現実的な案は見当たらなかった | - |

### 5.3 推奨

**FREE v1では案Aを推奨する。** 理由：

1. schema.orgが要求する数値形式と、02仕様書§10が要求する「自由記述による多様な料金形態への対応」は、データモデルレベルで本質的に相容れない。無理に変換しようとすると、実際には誤った・不完全な構造化データを出力するリスクの方が、Rich Resultを得られない機会損失より大きい。
2. Google自身、不正確・実態と異なる構造化データの使用をスパムポリシー違反として扱っており、無理な変換は将来的なペナルティリスクさえある。
3. 案Bは技術的に可能だが、「一部だけ構造化データが付く」という一貫性のなさと判定ロジックの継続的なメンテナンス負担に対して、得られるSEO実益（個々の料金Offerが検索結果の見た目に与える影響は限定的）が見合わない。
4. 案Cは仕様変更（新規フィールド）に該当し、本工程の権限を超える。

**結論：Price → Offer/PriceSpecificationの自動JSON-LD出力はFREE v1で実装しない。** 意味データとしてのPrice自体は既存どおり保持し、将来的にユーザー・仕様側から「数値専用の追加項目がどうしても必要」という要求が出た場合にのみ、正式な仕様判断（Decision）を経て再検討する。

---

## 6. FAQPage問題（Construction Order 004からの引き継ぎ事項）

### 6.1 2026年時点の最新事実（要・強調）

**Google検索のFAQ Rich Resultは2026年5月7日付で完全に廃止された。** 2023年8月の「著名な政府・医療機関サイト等への実質限定」からさらに進み、2026年5月には**あらゆるサイトでFAQのドロップダウン形式リッチリザルトが表示されなくなった**。2026年6月にはSearch ConsoleのFAQ検索パフォーマンスフィルタ・リッチリザルトレポート・リッチリザルトテストのFAQサポートも削除され、2026年8月にはSearch Console APIのFAQリッチリザルトデータサポートも削除されている。

`FAQPage`型自体はSchema.orgの正式な型として存続しており、Googleは技術文書上「既存のマークアップを残しても害はない（する必要はない）」としているが、**検索結果上の視覚的なメリットはFREE v1公開時点（2027年想定）で存在しない**。

出典：
- [FAQ Rich Results Deprecated: Google's May 2026 Change](https://www.getpassionfruit.com/blog/what-changed-with-google-drops-faq-rich-results-and-what-to-do-now)
- [FAQ Schema in 2026: What's Confirmed, What's not & What to do](https://www.quattr.com/blog/faq-schema-in-2026)
- [Google FAQPage after May 7, 2026: official guidance](https://fennecseo.app/blog/google-faq-structured-data-update/)

### 6.2 一般的な士業サイトでの実益・誤用リスク・メンテナンス負担の整理

| 観点 | 評価 |
|---|---|
| 実益 | **ゼロに近い**（Rich Result自体が存在しないため、視覚的な検索結果改善効果は見込めない） |
| 誤用リスク | 「FAQPageを出せばSEO効果がある」という誤った期待をユーザーに与えかねない。ASTREAの「SEOスコアゲームをしない」思想（AGENTS.md §13）に反する |
| Schema.orgとしての意味 | 型としては引き続き有効であり、「質問と回答」という意味構造を持つデータであることに変わりはない。将来Google以外の検索エンジン・AI検索（生成AI要約等）がFAQPageを再度活用する可能性はゼロではない |
| Google Rich Resultとの違い | 「Schema.orgとして正しい」ことと「検索結果で優遇される」ことは別問題であることが、この事例で明確になった |
| Maintenance負担 | JSON-LD生成自体は軽量だが、「Googleの仕様変更に追従し続ける」という継続コストが発生する。現状（Rich Result廃止済み）でこれを新規実装するのは、既に価値を失った機能へ工数を投じることになる |

### 6.3 推奨

**FREE v1では「出さない」を推奨する。** 理由：

1. 唯一の実益（Rich Result表示）が2026年5月時点でGoogle検索から完全に失われている。
2. 02仕様書§11・31・AGENTS.md §13が明示する「FAQをSEO目的の量産装置にしない」「SEOスコアゲームをしない」という思想と、価値のなくなった機能への投資は方向性が逆である。
3. 「条件付きで出す」（例：Google以外の検索エンジン向けに残す）は、判定基準を作ること自体が過剰実装であり、02仕様書のFAQ意味データはJSON-LDを介さずとも通常のHTML（Construction Order 004で実装済みのPlain Semantic HTML）として検索エンジンにクロールされる。
4. 「将来検討」は妥当な留保として明記する：Google以外の検索エンジンやAI検索の構造化データ活用方針が今後変化した場合、あるいはGoogleが別形式でFAQ的機能を復活させた場合は、その時点で改めて判断する。

**結論：FAQPage JSON-LDはFREE v1で実装せず、将来の検索エンジン動向次第で再検討する「将来検討」区分とする。**

---

## 7. Office / Professional Schema設計

### 7.1 型選定の一次情報

- `ProfessionalService`（一般用途の型）は**Schema.orgにより非推奨（deprecated）化されている**。理由は`Service`との意味的重複・混同。ただし`Attorney`・`AccountingService`等、`LocalBusiness`のサブタイプとして具体的な職種名を持つ型は非推奨の対象外で、引き続き有効。
- `LocalBusiness`は「実店舗・実際の拠点」を表す型であり、`Organization`は「組織そのもの」を表す、より一般的な型。
- 出典：[ProfessionalService - Schema.org Type](https://schema.org/ProfessionalService)、[LocalBusiness - Schema.org Type](https://schema.org/LocalBusiness)

### 7.2 ASTREA FREEでの判断

ASTREA FREEは全士業共通版であり、特定の職種名（弁護士＝`Attorney`、税理士等の具体的な英語表記が定まらない職種を含む）を前提にできない。したがって：

- **Office Profile → `Organization`** を採用する。`Attorney`等の具体的サブタイプはPRO（職種別自動化）の領域として将来検討する余地を残すが、FREE共通版で決め打ちしない。
- **一般用途の`ProfessionalService`は非推奨であるため使用しない**（仮に「専門家サービスらしさ」を表現したくなっても、非推奨型を新規採用することは技術的に不適切）。
- `LocalBusiness`（実店舗）についても、士業事務所の性質（複数拠点・オンライン相談のみの事務所等の多様性、Decision 022の法人・複数専門家対応）を踏まえ、FREE共通版としては`Organization`のほうが汎用的で安全。将来、住所・営業時間等の「実店舗性」を強調したい場合は`Organization`のサブタイプとしての`LocalBusiness`相当の拡張を個別検討する余地を残す（本工程では決定しない）。

### 7.3 Office ⇔ Professional の関係表現

- `Organization.employee`（値は`Person`の配列）を用い、公開済みのProfessional Profile全員を列挙する。`member`ではなく`employee`を採用する理由：`employee`は「組織に対する雇用・所属関係にある人物」という意味が士業事務所の実態（所属する専門家）に近く、`member`は会員制組織・団体寄りの含意があるため。
- 個人事務所（Professional Profile 0件）の場合：`employee`配列は空、または省略する。Organization自体のJSON-LDは代表者情報の有無に関わらず出力できる（Office Profile自体は代表者情報を含まないため、Decision 023以降は無関係）。
- 複数専門家・複数代表者（Decision 022・025）の場合：`employee`配列に全員を列挙する。**「代表者」という属性はSchema.org上に対応する標準プロパティが存在しないため、JSON-LD上で代表者フラグを特別扱いしない**（`employee`配列内での並び順を、Professional Profileの既存の確定表示順＝`menu_order`→`title`→`ID`に合わせることで、実務上「代表者を先頭に表示したい」という運用ニーズには対応できる。これは新しいSchemaプロパティを発明せず、既存の表示順ロジックを流用するだけで足りる）。
- 各`Person`のマッピング（すべて既存フィールドのみ使用、新規フィールド不要）：
  - `name` ← Professional Profileの氏名（`post_title`）
  - `jobTitle` ← 資格・肩書（`qualification`）
  - `description` ← 紹介文（`bio`、プレーンテキスト化して出力。Block Editor由来のHTMLをそのままJSON-LD文字列へ混入させない）
  - `image` ← 写真（featured image、絶対URLに変換して出力）
- Office Profileのマッピング：
  - `name` ← 事務所名
  - `address`（`PostalAddress`） ← 所在地
  - `telephone` ← 電話番号
  - `openingHoursSpecification` ← 営業時間（対応要否は本工程のスコープ外とし、Test Strategyの範囲で最小限に留める。詳細マッピングは施工時に設計する）
  - `url` ← サイトのホームURL

---

## 8. Breadcrumb設計

Decision 010によりすでに「視覚UI＝Theme、JSON-LD＝Core」という責任分担が確定している。本工程の課題は「両者が同じ情報源から生成され、食い違わないこと」の設計である。

- **単一の情報源**：WordPress標準の投稿階層・Taxonomy構造（`get_post_ancestors()`、投稿タイプのアーカイブ、Taxonomy Term階層等）をただ一つの正本とする。Breadcrumb専用の独自データを新設しない（Decision 010の明文どおり）。
- **Core**：この標準階層情報を読み取り、`BreadcrumbList`のJSON-LDを`wp_head`で出力する関数を1つ持つ。
- **Theme**：同じ標準階層情報を、WordPress標準のTemplate Part（`core/navigation`等の標準Blockか、投稿階層APIを呼ぶ最小限のPHP関数）で視覚的に描画する。
- **食い違い防止の具体策**：CoreがJSON-LD生成に使うのと**同一の階層解決ロジック**（例：`get_the_breadcrumb_items()`のような単一の内部ヘルパー関数）を、視覚UI側（Theme）からも呼び出せる形でCoreの公開APIとして提供する。ThemeとCoreが別々に「階層とは何か」を再実装すると食い違いのリスクが生まれるため、これを避ける。
- 対象範囲：投稿・固定ページの標準階層、Service/Professional Profileのアーカイブ・個別ページ（Construction Order 003/004で実装済みのCPT）。FAQ（Taxonomy `astrea_faq_category`）についても同じロジックで自然にカバーされる。

---

## 9. OGP / Social設計

### 9.1 対象タグの最小セット

- `og:title` / `og:description` / `og:url` / `og:type`（`website`または`article`のみを判定。細かいog:type分類はしない） / `og:image` / `og:site_name`
- `twitter:card`（`summary_large_image`固定でよい。X（旧Twitter）はOpen Graphタグへフォールバックするため、`twitter:title`等の個別複製は不要——出典：[Open Graph fallback on X, 2026年時点](https://richdevtools.com/articles/web/open-graph-meta-tags-guide)、[Twitter Card Meta Tags Guide 2026](https://seology.ai/blog/twitter-card-meta-tags-guide-2026)。ただし`twitter:card`自体を省略するとX上でカード表示自体がされない場合があるため、この1タグのみ追加する）
- SNSごとの個別最適化（Facebook専用、LINE専用等）は行わない（指示文の禁止事項どおり）。

### 9.2 画像Fallback順序（暫定案、施工時に確定）

1. ページ個別のOGP画像（将来、投稿ごとの明示設定が実装される場合）
2. Featured Image（WordPress標準）
3. サイト標準OGP画像（Core設定、02仕様書§17）
4. 画像なし（`og:image`を出力しない。ダミー画像は使わない——「項目を埋めなければ成立しない構造にしない」という既存原則に整合）

### 9.3 Office Profile / Site情報との関係

`og:site_name`はOffice Profileの事務所名を再利用する（同じ情報を複数箇所に入力させない原則）。`og:description`のフォールバックは投稿の抜粋（WordPress標準の`get_the_excerpt()`）を使う。

---

## 10. Search Console設計

Decision 009により範囲は既に確定している。本工程で再確認・明確化する点のみ記載する。

- **対応する方式はHTMLタグ方式のみ**（`<meta name="google-site-verification" content="...">`をトップページの`<head>`へ出力）。DNSレコード方式・Google Analytics連携方式・Google Tag Manager方式等、他の確認方式は実装しない。
- 確認コードは管理画面の1フィールド（テキスト入力）に保存し、値が空なら何も出力しない（「使わない場合は関連処理を読み込まない」という02§18の明文どおり）。
- Search Console API・OAuth連携・検索順位取得等は実装しない（Decision 009で確定済み、変更なし）。
- WordPress標準のSitemap URL（`/wp-sitemap.xml`）を管理画面上で案内し、Search Consoleへの登録手順（外部リンクとテキスト説明）を表示する。

出典：[Verify your site ownership – Search Console Help](https://support.google.com/webmasters/answer/9008080)（HTMLタグ方式の技術要件：`<head>`内・ホームページ・非ログイン状態でも到達可能、という制約を確認）

---

## 11. Security / Privacy

SEO出力はすべて未認証の訪問者が閲覧する公開HTMLの`<head>`に出るため、他機能以上に慎重な扱いが必要。

- **Sanitization / Escaping**：
  - meta description・OGPの`content`属性値は`esc_attr()`で必ずエスケープする。
  - JSON-LD内の文字列値は`wp_json_encode()`を使用し、手動での文字列連結によるJSON生成を行わない（自動的に適切なエスケープが行われるため、Injection経路を構造的に排除できる）。
  - URLは`esc_url()`を通す（Search Console verification codeやOGP画像URLがユーザー入力由来の場合、任意のプロトコル・スキームを許容しないようにする）。
- **Search Console verification codeのSanitization**：Google側が発行する値は英数字と`-`程度の限定文字種のはずだが、`sanitize_text_field()`を通したうえで`esc_attr()`出力する二重防御とする。
- **管理権限**：SEO設定変更は既存の他機能（Office Profile等）と同水準の`manage_options`を要求し、Nonce/Capability Checkを既存パターン（`register_setting()`のSettings API）に準拠させる。
- **Contact等の非公開データ混入防止**：Contact（Construction Order 005）は明示的に`show_in_rest => false`かつ非公開設計であり、SEO Foundationのどのコードからも`Astrea\Core\Inquiry`名前空間の関数を呼ばない（意味データ選別の対象にContactを含めない、という設計自体がこの防止策そのものである。§4の対象データ一覧にもContactを含めていない）。
- **JSON-LD Escape**：`wp_json_encode()`はデフォルトで`<`/`>`をエスケープしない場合があるため、`<script>`タグ内への埋め込み時は`JSON_HEX_TAG`等のオプション、またはWordPress標準の`wp_json_encode($data, JSON_UNESCAPED_UNICODE)`に加えてスクリプトタグ脱出を防ぐ追加エスケープを施工時に検証する。

---

## 12. Core無し / Core無効化

Decision 021・024の原則をそのまま適用する。

- **Core無し**：Theme自体はWordPress標準のtitle-tag/canonical/robots/Sitemapで最低限のSEOが機能する状態を維持する（§2の整理どおり、これらはCore非依存でWordPress自身が担保する）。meta description・OGP・JSON-LDはCore機能のため出力されないが、これは「壊れる」のではなく「単に出ない」状態であり、Decision 021が要求する「Fatal/Warning/Noticeを出さない」を満たす。
- **Core無効化**：直前まで出ていたmeta description/OGP/JSON-LDが消える（Decision 024の「Core所有データの残留表示無し」に整合——古いOffice Profile由来の`og:site_name`等が有効化中のキャッシュ等から残留表示されないよう、Block Bindings同様に都度動的生成する設計とし、静的ファイル等へのキャッシュ書き出しは行わない）。
- **Core再有効化**：即座に正常復帰する（Office Profile等と同じ、状態を持たない都度読み取り設計であるため、追加のMigration/復旧処理は不要）。
- **Theme単独時にWordPress標準SEOを邪魔しない**：Theme側のコードは`wp_head`・`wp_robots`・`document_title_parts`等のCore標準フィルタへ干渉するコードを一切書かない（既存のTheme実装方針——Core検出のみ行い、Core側の処理に介入しない——を継続する）。

---

## 13. Test Strategy（施工時の実装候補）

正式仕様に対応する範囲へ絞った最小限のTestを設計する。

| 分類 | 検証内容 |
|---|---|
| title | WordPress標準`title-tag`サポートが有効であることの確認（Theme側） |
| canonical | WordPress標準`rel_canonical()`が阻害されていないことの確認 |
| robots | `wp_robots`フィルタ経由での値追加が正しく反映されること（例：noindex設定時） |
| meta description | Office Profile／投稿抜粋からの生成、Core無効時に出力されないこと、XSS入力のエスケープ |
| OGP | 各タグの出力、画像Fallback順序（個別画像なし→Featured Image→サイト標準→なし）、Core無効時の非出力 |
| Schema JSON妥当性 | 出力されたJSON-LDが有効なJSONとしてパース可能であること（`json_decode()`でエラーが出ないこと） |
| Office 0/1 | Office Profile未設定時にOrganization JSON-LDが壊れた形で出ないこと（項目が空でも構造として成立すること） |
| Professional 0/1/N | `employee`配列が0件（省略）・1件・複数件で正しく生成されること |
| 複数代表者 | 代表者フラグがJSON-LD構造に影響しないこと（representativeを特別扱いしていないことの確認） |
| Service 0/1/N | Serviceを構造化データの対象にしない、という本工程の判断どおり出力されないこと（Serviceが構造化データ選定外であることの回帰確認） |
| Price自由記述 | Offer/PriceSpecificationのJSON-LDが一切出力されないことの確認（§5の判断どおり） |
| FAQ 0/1/N | FAQPage JSON-LDが一切出力されないことの確認（§6の判断どおり） |
| Breadcrumb | 視覚UIとJSON-LDが同一の階層情報から生成され、内容が一致すること |
| Search Console verification | 値設定時のみ出力、未設定時は非出力、Sanitizationの確認 |
| XSS / Injection | 各種入力（事務所名・資格肩書・紹介文等）にスクリプトタグを含めた場合の出力エスケープ確認、JSON-LD内でのスクリプトタグ脱出不可の確認 |
| Core無し | Theme単体でFatal/Warning/Noticeが出ないこと |
| Core無効化 | 直前まで出ていたSEO出力が消えること、Theme標準SEOが引き続き機能すること |
| SEO Plugin共存 | 既知Pluginシグネチャ検出時に該当ASTREA出力が自動停止すること（実機で主要Pluginを1つ導入して確認） |
| 重複metadata防止 | 二重出力が発生しないこと（同上のPlugin共存Testと重複するため一体で検証してよい） |
| 001〜005 Regression | 既存smoke-test.sh Part1〜6が引き続きPASSすること |
| PHPCS / PHPUnit / wp-env実HTTP / GitHub Actions | 既存の3層検証体制をそのまま踏襲する |

不要と判断し削った項目：SNS個別対応Test（Twitter Card以外の個別プラットフォーム対応をしない方針のため）、Search Console API関連Test（実装しないため）。

---

## 14. Web調査の記録（一次情報の出典まとめ）

すべて2026-08-27（JST）に確認。

1. [FAQ Rich Results Deprecated: Google's May 2026 Change](https://www.getpassionfruit.com/blog/what-changed-with-google-drops-faq-rich-results-and-what-to-do-now) — FAQPage Rich Resultの完全廃止（2026年5月7日）
2. [FAQ Schema in 2026: What's Confirmed, What's not & What to do](https://www.quattr.com/blog/faq-schema-in-2026)
3. [Google FAQPage after May 7, 2026: official guidance](https://fennecseo.app/blog/google-faq-structured-data-update/)
4. [`price` – Schema.org Property](https://schema.org/price) — 価格は数値または数値のみの文字列であるべきという明記
5. [PriceSpecification Clarifications – W3C Wiki](https://www.w3.org/wiki/WebSchemas/PriceClarifications)
6. [ProfessionalService - Schema.org Type](https://schema.org/ProfessionalService) — 一般用途のProfessionalServiceは非推奨
7. [LocalBusiness - Schema.org Type](https://schema.org/LocalBusiness)
8. [`employee` – Schema.org Property](https://schema.org/employee)
9. [`wp_get_canonical_url()` – WordPress Developer Resources](https://developer.wordpress.org/reference/functions/wp_get_canonical_url/)
10. [`get_canonical_url` フィルタ – WordPress Developer Resources](https://developer.wordpress.org/reference/hooks/get_canonical_url/)
11. [`wp_robots()` – WordPress Developer Resources](https://developer.wordpress.org/reference/functions/wp_robots/)
12. [WordPress 5.5 Sitemap機能 – Make WordPress Core](https://make.wordpress.org/core/2020/07/22/new-xml-sitemaps-functionality-in-wordpress-5-5/)
13. [WordPress Sitemaps Explained – Elementor Blog](https://elementor.com/blog/wordpress-sitemaps-explained/)（Core標準の限界＝meta description/構造化データが範囲外である点の裏付けとして利用。一次情報ではなく解説記事のため補助的参照に留める）
14. [Open Graph Meta Tags: A Practical Setup Guide](https://richdevtools.com/articles/web/open-graph-meta-tags-guide) — XのOpen Graphフォールバック挙動
15. [Twitter Card Meta Tags Guide 2026](https://seology.ai/blog/twitter-card-meta-tags-guide-2026)
16. [Verify your site ownership – Search Console Help](https://support.google.com/webmasters/answer/9008080) — HTMLタグ確認方式の技術要件（Google公式一次情報）

---

## 15. 発見した仕様上の要確認事項

指示文の停止条件（Decision同士の矛盾／Google現行仕様との重大矛盾／FREE-PRO境界変更／新規入力項目／既存意味データモデル変更）に該当する事項は発見しなかった。ただし、以下は独自判断で確定させず、正式な確認を推奨する：

1. **Price → Offer/PriceSpecificationを将来的にも一切出力しない方針でよいか。** 本書は「FREE v1では出力しない」を推奨したが、これは新規フィールド追加（案C）を伴わない限りの結論である。将来、税理士報酬規程のように定型的な数値化が可能な士業が現れた場合（Profession PRO側での職種別対応）に再検討する余地があることを明記しておく。
2. **Office Profileの営業時間（`business_hours`）をSchema.orgの`openingHoursSpecification`へどこまで対応付けるか。** データ自体は存在するが、臨時休業・年末年始等の期間データとschema.orgの`openingHoursSpecification`（曜日ベースの週次繰り返しが基本）との対応関係は本書で詳細設計しておらず、施工時に個別判断が必要（新しいDecisionを要するほどの重大性はないと判断するが、念のため明記する）。
3. **SEO Plugin検出のシグネチャリストに含める具体的Plugin名（Yoast SEO／All in One SEO／Rank Math／SEOPress等）の初期選定基準。** 本書は「日本語圏で高シェアのもの」という方向性のみ示した。正式なリストは施工時に確定してよいと判断するが、Decision 018の「既知リストへの追加式」という運用方針の範囲内であることは確認済み。
