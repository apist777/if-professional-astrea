# CONSTRUCTION ORDER 003 — Professional Profile / 複数専門家基盤 実施報告

- 実施日: 2026-08-26（JST）
- 担当: クロエ（Claude）
- 対象: Professional Profileデータ層、複数件対応、Media連携、Core公開境界、Theme最小表示、Test/CI拡張
- 基準文書: `docs/specifications/05_astrea_free_v1_construction_baseline.md`、Decision 001〜022
- 仕様変更: なし

---

## 1. 保存方式の比較と採用理由

| 方式 | 評価 |
|---|---|
| **Custom Post Type + Post Meta（採用）** | WordPress標準の「0〜複数件、一覧・編集・削除・並び順・メディア関連付けが必要なコンテンツ」に対する第一候補。投稿タイトル＝氏名、本文＝紹介文、アイキャッチ画像＝写真、が標準機能へそのまま乗る。管理画面（一覧・新規追加・編集・ゴミ箱）もWordPress標準UIをそのまま使え、独自Formを書く必要がない |
| Options APIによる構造化データ（配列に複数件格納） | Office Profileでは単一レコードに適していたが、複数件・メディア関連付け・個別編集・個別削除を要する本件には不向き。1つのOptionに全員分を詰め込むと、同時編集の競合、メディアとの関連付け、個別URLでの単体表示等をすべて自前で再実装することになり、WordPress標準を尊重する原則に反する |
| 独自DB Table | 第一選択にしない方針のとおり、不要と判断。CPT + Post Metaで要件を満たせるため、独自Tableが必要という判断には至らなかった。**（実装せず、方針として不要と判断。仕様上の要確認事項としての報告は不要と判断）** |

**採用**：Custom Post Type `astrea_professional`（`core/includes/professional-profile.php`）。

---

## 2. Professional Profile 実装項目

02仕様書§8（Decision 022により「Professional Profile」と改称）に列挙された項目のみを実装した。新規項目の追加は行っていない。

| 項目 | 保存先 | 備考 |
|---|---|---|
| 氏名 | `post_title` | WordPress標準 |
| 紹介文 | `post_content` | WordPress標準Block Editor + 標準Kses Sanitization |
| 写真 | アイキャッチ画像（Media Library） | Core独自の複製保存は行わない |
| 資格・肩書 | postmeta `astrea_professional_qualification` | 独自Meta Box、`sanitize_text_field` |
| 経歴 | postmeta `astrea_professional_career` | 独自Meta Box、`sanitize_textarea_field` |
| 学歴 | postmeta `astrea_professional_education` | 独自Meta Box、`sanitize_textarea_field` |
| 所属 | postmeta `astrea_professional_affiliation` | 独自Meta Box、`sanitize_text_field` |
| 登録情報 | postmeta `astrea_professional_registration_info` | 独自Meta Box、`sanitize_text_field` |

**すべての項目が任意**であり、氏名も含めストレージ層で必須化していない（02仕様書§8「すべて任意とし、項目を埋めなければデザインが成立しない構造にはしない」を根拠に、氏名を含むどの項目にも`required`制約を設けていない）。

Office側（法人番号・資本金・設立年月日等）、CTA・相談方法、ACCESS固有情報（最寄駅等）は、Decision 022どおりProfessional Profileへ含めていない。

---

## 3. 複数人対応・表示順

- Office 1件に対しProfessional Profileは0〜N件。**Office側とのFK（所属Office ID）は設けていない**：FREE v1は単一サイト＝単一Officeを前提とするため、サイト上の全`astrea_professional`投稿が暗黙に「そのサイトのOffice」に属する。これは省略ではなく、単一サイト構成である以上FKを持たせても常に同じ値になるだけであり、不要な複雑化を避けた設計判断。
- 新規登録・編集・削除・一覧は、WordPress標準の投稿編集画面・投稿一覧画面をそのまま使用する（独自管理UIは追加していない）。
- 表示順は投稿タイプに`page-attributes`サポートを付与し、WordPress標準の「並び順」入力欄（Order）をそのまま利用する。独自Drag & Drop UIは導入していない。
- `menu_order`は新規投稿時に既定で0になるため、同順位（未設定）が発生しうる。`pre_get_posts`フックで、明示的な`orderby`指定がない場合に限り`menu_order → title → ID`の順で確定的にソートするフォールバックを実装し、同順位・未設定でも表示が不安定にならないことを保証した（実機検証済み。セクション7参照）。

---

## 4. Media Library連携・写真の所有権

- 写真はWordPress標準のアイキャッチ画像（Featured Image）機能を利用する。Core独自のアップロード領域・複製保存は一切行わない。
- Professional Profile側は、投稿に紐づく`_thumbnail_id`（WordPress標準機構）を参照するのみで、画像バイナリ・メタデータの複製は持たない。
- 画像未設定でもMarkupは壊れない（`core/post-featured-image`ブロックは何も出力しないだけで、Fatal/Warningを発生させない。実機確認済み）。
- 添付ファイルが削除された等でアイキャッチIDが無効な場合、`get_profile()`は`photo_id`を`null`として返す（`get_post_type($photo_id) === 'attachment'`によるガード）。フロントエンド側（`core/post-featured-image`）も同様にFatal/Warningなく何も表示しないことを実機確認済み。
- **Accessibility（alt）の責任境界**：alt属性はWordPress Media Library標準の添付ファイルメタデータ（`_wp_attachment_image_alt`、メディア編集画面で設定）にすでに存在する。Professional Profile側に独自のalt入力欄は設けていない。これはCore独自実装を増やさない判断であり、alt管理はユーザーが所有するMedia Library資産の一部として扱う。

---

## 5. Core公開取得境界

- `Astrea\Core\ProfessionalProfile\get_profile( int $post_id ): ?array` — 単一取得。存在しないID、他投稿タイプのID、非公開状態のIDに対しては`null`を返す（不正ID参照防止）。
- `Astrea\Core\ProfessionalProfile\get_profiles(): array` — 公開済み全件を確定順で取得。
- Theme／将来のPROは、`astrea_professional`という投稿タイプ名と`astrea_professional_*`というMeta Key名（技術的契約）、およびこれら2つの関数を通じてのみProfessional Profileへアクセスする。Core内部のClass構成やMeta保存方式の詳細に依存させない。

---

## 6. Themeとの接続方式（比較・採用理由）

Construction Order 003 §8の指示に従い、以下を比較した。

| 方式 | 適合性 |
|---|---|
| **Query Loop（`core/query`）+ 標準Block一式（採用）** | 複数件・一覧というPROFILEの性質に最も自然に合致する。`core/post-featured-image`・`core/post-title`・`core/post-excerpt`・`core/query-title`はすべてWordPress標準Block。Core側は投稿タイプを登録するだけで、Theme側は投稿タイプ名を指定したQuery Loopを配置するだけで接続が完結する |
| Block Bindings単体 | スカラー値（Office Profileの電話番号等）には適するが、複数件の一覧表示という性質上、単一のBlock属性に単一の値を紐づけるBlock Bindingsだけでは実現できない（Order自身も「Block Bindingsだけで無理に実現しない」と指示） |
| 独自Dynamic Block | 一覧表示を実現できるが、WordPress標準のQuery Loopで完全に代替できるため、独自Block実装は不要な独自実装の増殖にあたる |

**個別のpostmeta値（資格・肩書）の表示**には、WordPressコア組み込みの`core/post-meta`Block Bindings Sourceを採用した。これはCore側が独自のBinding Source登録コードを書く必要が一切ない、WordPress標準機構そのものであり、Office Profile（Construction Order 002）で採用した独自Binding Source登録よりもさらに標準準拠度が高い。

`theme/templates/archive-astrea_professional.html`を新規作成し、`core/query-title`（H1）→`core/query`（`inherit:true`でCoreの`pre_get_posts`による確定順ソートを自然に受け継ぐ）→ 各投稿ごとに`core/post-featured-image`・`core/post-title`（H2）・postmeta連動段落・`core/post-excerpt`、という構成にした。独自CSSは追加していない。

---

## 7. Core無し／無効化時の実機検証結果

wp-env上で以下をすべて実際のHTTPリクエストで確認した。

| # | 検証内容 | 結果 |
|---|---|---|
| — | Professional Profile 0件時のアーカイブ | HTTP 200、Fatalなし |
| — | 3件登録（`menu_order`同値の同順位を含む）＋下書き1件 | アーカイブで公開3件のみ、`menu_order`→タイトルの確定順で表示。下書きは非表示 |
| — | postmeta（資格・肩書）の`core/post-meta` Binding表示 | 正しく表示 |
| — | 写真あり（1件） | `<img>`が正しく出力 |
| — | 写真なし | 壊れた`<img>`なし、Fatal/Warningなし |
| — | 不正・消失Attachment ID参照 | `get_profile()`は`photo_id: null`、フロントエンドも何も出力せずFatal/Warningなし |
| — | Core無効化 | ホームページ・アーカイブともにHTTP 200を維持。古いProfessional Profile名の残留表示なし。debug.logへの出力なし |
| — | Core無効化中のデータ保持 | DB（`wp_posts`）に投稿が残存していることを直接クエリで確認 |
| — | Core再有効化 | アーカイブの表示・確定順が完全に復元 |

すべて`tools/ci/smoke-test.sh`（J〜S）として自動化し、ローカルで2回連続実行して冪等性も確認した。

**発見した技術的ニュアンス（バグではないが記録する）**：Core無効化中に`/professionals/`へアクセスした場合、投稿タイプが未登録になるためリライトルールが実質的に機能せず、期待されるような明確な404ではなく**HTTP 200（サイトのトップページ相当のフォールバック）**が返る。Fatal・Warning・古いデータの残留表示は一切発生しないため、Construction Order 003が要求する安全性（Fatal/Warning/Notice/壊れたMarkup/古いProfile値の残留表示を発生させない）はすべて満たしている。ただし「Core機能のURLがCore無効時に正しく404を返す」という体験の質については、今回のスコープを超えるため対応していない。将来必要であればTheme側で対応を検討する。

---

## 8. Security

- 投稿本体のCapability制御はWordPress標準の投稿タイプCapabilityマッピングにそのまま委ねる（独自の権限体系を作らない）。
- 追加Meta Box（資格・肩書等5項目）の保存は、Office Profile（Construction Order 002）と同水準のNonce（`wp_nonce_field`/`wp_verify_nonce`）・Capability（`current_user_can('edit_post', $post_id)`）・Sanitization（`sanitize_text_field`/`sanitize_textarea_field`のみ、固定の許可リストから選択）を実装した。
- 保存処理は`meta_sanitizers()`で定義された既知の5キーのみを処理し、リクエストに含まれるその他の任意のMeta Keyは無視する（不正なMeta Key注入の防止）。
- PHPUnitで、Nonce欠落・Nonce不正・権限のないユーザー（subscriber）による保存試行がすべて拒否されることを検証済み（`test_save_meta_rejects_missing_nonce`、`test_save_meta_rejects_invalid_nonce`、`test_save_meta_rejects_non_capable_user`、`test_save_meta_ignores_unknown_meta_keys`）。

---

## 9. Migration / Schema

**現時点でMigration機構は不要と判断した。** 理由：Office Profileと異なり、Professional ProfileはWordPress標準の投稿・postmeta構造をそのまま利用しており、ASTREA独自のスキーマバージョン管理対象となる「独自の内部データ形状」を持たない。

将来Migrationが必要になる発火条件：

- postmetaキー名（`astrea_professional_*`）を変更・統合する場合
- 単純文字列フィールドを構造化配列（例：経歴を年ごとのリスト化）へ変更する場合
- Office Profileのように0..N件のデータを単一Optionへ格納する方式へ将来変更する場合（現状は投稿単位のため該当しない）

---

## 10. Uninstall / Data Ownership

`core/uninstall.php`を更新し、`astrea_professional`投稿・そのpostmetaをOffice Profileと同列のCore所有データとして明記した（削除処理は追加していない＝現状維持）。

特記事項として、**Professional Profileの写真（Media Libraryの添付ファイル）は、将来の「Core所有データの完全削除」フローが実装された場合でも削除対象に含めない**ことを明文化した。添付ファイルはWordPressの通常の「投稿削除時にアイキャッチのみの関連付けを解除し、添付ファイル自体は削除しない」という標準動作と一致させる。

---

## 11. Accessibility

- 見出し階層：`core/query-title`（H1）→ 各Professional Profileの`core/post-title`（H2）という一貫した階層とした。H1を飛ばしてH2から始める等の階層スキップは発生しない。
- alt：セクション4のとおりMedia Library標準機構に委任。
- 画像だけに依存しない：氏名（テキスト）・資格肩書（テキスト）・紹介文（テキスト）が写真の有無に関わらず表示される構造。
- Keyboard / Focus：独自JS・独自インタラクティブ要素を追加していないため、WordPressコア標準Blockの既存Accessibility特性がそのまま適用される。
- Semantic Markup：`<article>`要素で各Professional Profileを区切っている。

---

## 12. Test / CI結果

- **PHPUnit**：`tests/ProfessionalProfileTest.php`（19件）+ 既存`tests/OfficeProfileTest.php`（20件）＝**39 tests / 64 assertions、全PASS**。
- **PHPCS**：エラー・警告0件（実PHP 8.3環境、`wp-env`の`tests-cli`コンテナ経由）。
- **smoke-test.sh**：既存A〜I（Construction 001/002）は無変更のままRegression PASS。新規J〜S（Professional Profile）を追加し全PASS。2回連続実行して冪等性を確認。
- **GitHub Actions**：詳細は本報告書末尾（セクション0、最終検収後に追記）参照。

---

## 13. 発見した仕様上の要確認事項

1. **Office Profileの「代表者名」とProfessional Profileの重複可能性。** Construction Order 003の指示どおり、Office Profile（Construction Order 002）の`representative_name`フィールドは削除・移動していない。しかし、Professional Profileが実装された今、「代表者」は本来Professional Profileの中の1件（例えば`menu_order`が最も若い、または何らかのフラグを持つ1人）であるべきではないか、という設計上の重複・UX課題が存在する。現状は、Office Profileの`representative_name`（単純文字列）とProfessional Profile（構造化された複数人データ）が独立して併存しており、同じ「代表者名」を将来的に2箇所で管理・編集できてしまう可能性がある。**クロエの判断でこの重複を解消することはしていない。** 次回の仕様確認を推奨する。
2. Core無効化時の`/professionals/`がクリーンな404ではなくHTTP 200のフォールバックになる点（セクション7参照）。安全性要件は満たすが、URL設計の観点で改善余地がある。
3. 上記以外に、Baseline / Decision 001〜022との矛盾は発見していない。
