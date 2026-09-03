# Construction 016F Phase 1 — Internal Page Audit（施工前）

コード変更前に、既存Fixtureで内部ページを実機確認した。全ページ共通のTemplate構造（`<main class="wp-block-group" style="layout:constrained">`、`align:full`無し）がHOMEと同じ「Core is-layout-constrained強制収縮」バグの対象になっており、Breadcrumb→H1→本文がすべて狭いcontentSize(720px相当)に収まっていることを確認した。

| Page | Visual Score /100 | Primary Problem | HOME v3との差 | Required Action | Risk | Priority |
|---|---|---|---|---|---|---|
| Office (事務所概要) | 55 | 情報表はSurface panelで読みやすいが、Page全体がWide Gridを使わず、Hero相当のFirst Viewが皆無 | Kicker/Wide Grid/Photography無し | Page Header System追加、Wide Grid化 | Low | A |
| Professional Archive | 45 | 小さな円形Avatar+Surfaceカードの「Staffディレクトリ」、Photographyが主役でない | Editorial言語との断絶が大きい | Photo主役のEditorial Card化、Wide Grid | Medium | A |
| Professional Single | 50 | 経歴/学歴/所属/登録情報が単なる見出し+段落の縦積み(dl的)、視覚Hierarchy無し | 情報の構造化データ表示が弱い | Structured Data Presentation化 | Medium | A |
| Service Archive | 55 | Numbering/Icon無し、狭い2-column Surfaceカード | HOME Serviceの01/02/03+Icon DNAと不一致 | Icon+Number統合、Wide Grid | Low | A |
| Service Single | 65 | 関連取扱業務は既にHOME Service CSSを共有し良好、Closing CTAも既存。上部Page HeaderのみWide Grid未適用 | 部分的に良好 | Page Header Wide Grid化のみ | Low | A |
| CASE Archive | 60 | Photo Cardは悪くないが2-column・狭幅、Numbering無し | HOME CASEのBadge/3-column DNAと不一致 | Wide Grid化、Badge統合 | Medium | A |
| CASE Single | 65 | 関連取扱業務・Closing CTA良好、上部のみ狭い | 部分的に良好 | Page Header Wide Grid化のみ | Low | A |
| Price | 45 | Icon無し、単純な横並びテキスト行の羅列、Closing CTA無し | HOME Priceの4-column+Icon DNAと大きく乖離 | Icon付与、Wide Grid、Closing CTA追加 | Medium | A |
| Contact | 50 | イントロ文無し、Label階層が弱い、標準WordPress Formの見た目 | Visual v3のTypography/Spacing未適用 | Intro追加、Form Visual強化 | Low | A |
| FAQ Archive | 40 | 旧Blue-left-borderカード(016D-R2以前のスタイル)のまま、Wide Grid無し | HOMEの現行FAQ(Gold Q/Aラベル)と別デザイン | HOME現行FAQスタイルへ統合 | Low | B |
| VOICE Archive | 40 | 旧灰色角丸カード(016D-R2以前のスタイル)のまま、Wide Grid無し | HOMEの現行VOICE(restrained panel)と別デザイン | HOME現行VOICEスタイルへ統合 | Low | B |
| Search Results | 35 | 完全に無装飾、Wide Grid無し、Page Header無し | 一般的なWordPress検索結果そのまま | Page Header + Wide Grid化 | Low | B |
| 404 | 50 | シンプルだが機能的、Wide Grid無し | Page Header的処理が無い | Wide Grid化、軽い整形 | Low | B |

**全体所見**: 個々のPage単位のバグではなく、Template Architecture全体（`main`に`align:full`が無いことによるWide Grid未適用）が共通根本原因。HOMEで3度発見・修正した同じCore `is-layout-constrained`収縮パターンが、内部ページ全体で未修正のまま残っていた。
