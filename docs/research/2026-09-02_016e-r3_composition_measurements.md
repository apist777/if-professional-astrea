# Construction 016E-R3 — HOME Section Composition Finalization Measurement Audit

1920/1440/1366px、Playwright実機計測。CSS変更前に現状を計測してから施工した（§2）。

## Before Geometry Table（1440px基準）

| Section境界 | Heading→Content Gap | Section→Section Gap |
|---|---|---|
| Services (Heading→Cards) | 56px | — |
| CASE (Heading→Cards) | 56px | Services→CASE: 96px |
| Results (Heading→Metrics) | 56px | CASE→Results: 230px |
| Professional (Heading→Photo/Bio) | 56px | **Results→Professional: 0px** |
| Price (Heading→4-col) | 56px | **Professional→Price: 0px** |
| CTA | — | Price→CTA: 24px |
| FAQ (Heading→Q&A) | 56px | CTA→FAQ: 0px |
| Voice (Heading→Testimonials) | 56px | FAQ→Voice: 96px |
| Flow (Heading→Steps) | 56px | Voice→Flow: 121px |

**根本問題**: Heading→Content Gap自体は数値上56pxで統一されていたが、CASE/Results/Voiceは「Heading=白背景」「Body=Surface/Dark背景」という**色の分離**により、同じ56pxでも視覚的にHeadingとBodyが別Sectionに見えていた。単なるmargin値の問題ではなく、Compositionの構造問題だった。

## After（016E-R3実装後）

| Section | Heading背景 | Body背景 | Heading→Body処理 |
|---|---|---|---|
| Services | 白 | 白 | margin（heading-gap token、56px→40pxへ調整） |
| **CASE** | **Surface（統合）** | Surface | **padding、margin-bottom:0（一体化）** |
| **Results** | **Contrast（統合、白文字）** | Contrast | **padding、margin-bottom:0（一体化）** |
| Professional | 白 | 白 | margin（40px） |
| Price | 白 | 白 | margin（40px） |
| **FAQ** | 白（左カラム） | 白（右カラム） | **2-column Grid（新規wrapper）** |
| **Voice** | **Surface（統合）** | Surface | **padding、margin-bottom:0（一体化）** |
| Flow | 白 | 白 | margin（40px）、Section自体のpadding large→medium |

## Heading-Gap Token

| Token | Before | After |
|---|---|---|
| `--astrea-v3-heading-gap` | `var(--wp--preset--spacing--large)`（56px） | `2.5rem`（40px） |

Services/Professional/Price/Flowで共有される値をToken単位で調整（個別Section毎の数値ではなく、1箇所のToken変更）。

## Section→Section Gap（変更なし、016E-R2で確立した`--astrea-v3-section-gap`(96px)を継続使用）

CASE/Results/Voiceの統合Panel化により、これらのSectionの「内部」のHeading→Body距離は`--astrea-v3-heading-gap`ではなく個別のpadding値（large/medium token）へ切り替わったが、Section同士の外側の呼吸（例: Services→CASE）は引き続き`--astrea-v3-section-gap`を使用しており、変更していない。

## FAQ 2-Column実装

| 項目 | Before | After |
|---|---|---|
| 構成 | 単一カラム（左寄せ、max-width:960px） | 2-column Grid（左260-380px: Kicker+Heading、右1fr: 既存FAQ list） |
| 右半分の死角 | あり（Wide Grid frameの約40%のみ使用） | 解消（右カラムに既存FAQ listがそのまま配置され、Grid全幅を実質使用） |
| データ/機能変更 | — | なし（PHPの`render_faq_list_block()`へheading設定時のみ適用されるラッパーdivを追加、既存Archiveページは影響を受けないことを確認済み） |

## Price→CTA接続

| 項目 | Before | After |
|---|---|---|
| Gap | 24px | 24px（変更なし、016E-R2から意図的に維持） |

実測・目視確認の結果、24pxのgapは既にPrice直後にCTAが直結して見える状態であり、追加の調整は不要と判断した。

## Hero Collision再検証（§14必須）

`hero-safety-verify.js`を再実行し、6構成×3要素=18ケース全てSAFEを確認（Heroは本Order原則LOCK、変更なし）。

## 発見した実バグ（正直な報告）

FAQ 2-column Grid実装の`grid-template-columns:1fr`（782px以下向けMobile Override）を、既存の782pxメディアクエリブロックの先頭付近に追記した結果、同じ詳細度の無条件ベースルール（Stylesheet後方に位置）に負け、320/375px幅で実際に68px幅までFAQ itemが圧縮される横Overflow（375px時81px、320px時136px）が発生した。Spec 07番§21に既に文書化されていた「新しいResponsive OverrideはStylesheet末尾に配置する」という恒久原則を、実装時に見落として再発させたもの。Stylesheet末尾へ移動し解消、7幅全てOverflow 0pxを再確認した。
