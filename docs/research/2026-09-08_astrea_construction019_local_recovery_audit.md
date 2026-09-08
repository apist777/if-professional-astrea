# Construction 023-PRE — Construction 019 Local Demo Recovery & Migration Preflight

「やまだ行政書士事務所」ローカル発掘・復元監査

- Start: 2026-09-08 17:41:09 JST（実測）
- End: 2026-09-08 17:47:27 JST（実測）
- Duration: 0:06:18
- Modifier: Chloe
- Mode: READ-ONLY RECOVERY AUDIT（LIVE DEMOは今回一切公開せず）

## 1. Executive Summary — Recovery Verdict

**D. VISUAL EVIDENCE ONLY**

Construction 019で実際に構築・Owner受入された「やまだ行政書士事務所」は、**Construction 019のOrder自身が実行した安全設計により、トライアル終了後に意図的に破棄された使い捨てDocker環境（MySQL 8.0 + `wordpress:php8.3-apache`）の中にのみ存在していた**。DB・uploads・WordPressサイトそのものは現在一切残っていない。

現存するのは以下の2点のみ:

1. `docs/research/2026-09-05_construction_019_rc2_owner_final_acceptance_report.md`（施工報告書、161行）
2. `docs/research/screenshots/019/`（42枚のスクリーンショット、証拠画像）

さらに重要な追加発見として、**現存するスクリーンショットを実際に目視確認した結果、「代表者写真」および「対応事例#1の画像」は、いずれも実際の写真ではなく、単色（ネイビー系グレー）の単色プレースホルダー画像であったことが判明した**（§8参照）。すなわち、DB/uploadsが仮に残っていたとしても、そこに含まれていた「写真」は実写真ではない。

## 2. Repository State（§1）

- Repository: `~/if-professional-astrea`（想定通り）
- Branch: `main` / HEAD: `a0426a2`（Construction 022B）
- Tags: `v1.0.0`, `v1.0.0-rc2`, `v1.0.1`, `v1.0.2`
- `git status --short`: 既存の無関係な未追跡ファイル2点のみ（`docs/research/references/...Zone.Identifier`、`if-professional-astrea.code-workspace`）。本監査で一切変更なし。
- `.wp-env.json`: PHP 8.3、`wp-content/themes/astrea`←`./theme`、`wp-content/plugins/astrea-core`←`./core` のマッピング（開発用の永続wp-env環境。Construction 019の使い捨て環境とは**別物**）。
- `npx wp-env status`: `stopped`（install path `~/wp-env/wp-env-if-professional-astrea-57b77809`）。開発用永続環境は起動していないが存在する。
- Docker CLI: このWSLディストリビューションからは直接`docker`コマンドを実行できない（Docker Desktop WSL統合が本ディストリビューション向けに有効化されていないため、既存セッションでも既知の制約）。`docker ps -a`／`docker volume ls`による生のコンテナ/ボリューム列挙は本セッションでは実施不可だった。この制約により、Construction 019の使い捨てコンテナペアが本当に存在しないことを、私自身のDocker直接問い合わせで独立に再確認することはできなかった。判断は施工報告書自身の明示的な記述（後述§3）を一次証拠として採用している。

## 3. Construction 019 Evidence（§2）

`docs/research/2026-09-05_construction_019_rc2_owner_final_acceptance_report.md`より、証拠に基づき確定した事実（推測・補完なし）:

| 項目 | 値 | 根拠 |
| --- | --- | --- |
| 実施日時 | 2026-09-05 09:46 JST〜10:56 JST（HISTORY.csv実測） | HISTORY.csv row 8 |
| 使用Theme version | `astrea-theme-1.0.0-rc2.zip`（161,596 bytes） | 報告書§2 |
| 使用Core version | `astrea-core-1.0.0-rc2.zip`（159,849 bytes） | 報告書§2 |
| WordPress version | 7.1 | 報告書§3、スクリーンショット右下 "Version 7.1" |
| PHP version | 8.3.33 | 報告書§3 |
| Local URL/port | **証拠から確定不可**（報告書に具体的なURL/ポート番号の記載なし） | — |
| Site title | 「やまだ行政書士事務所」 | 報告書§3、スクリーンショット全体 |
| 環境の性質 | 「brand-new disposable」「never used for ASTREA development」使い捨てコンテナペア | 報告書§3 |
| 環境の末路 | **「Both containers were destroyed after the trial.」（施工報告書が明示的に記載）** | 報告書§3 |
| 本Orderによる製品コード変更 | 0件（`git diff --stat`出力なし、報告書§20/§21で確認済み） | 報告書§20、本監査でcommit `8f737c3`のdiff statを再確認 |

**確定した最重要事実: Construction 019のWordPress環境（DB・uploads含む）は、Order自身の設計により意図的に破棄されている。**

## 4. Local WordPress / Docker Discovery（§3）

- Construction 019は`.wp-env.json`が管理する開発用永続環境とは**別の**、手動作成の使い捨てコンテナペアを使用（報告書§3の文言「a brand-new disposable MySQL 8.0 + wordpress:php8.3-apache container pair」「never used for ASTREA development」から明確に区別できる）。
- 開発用永続wp-env環境（`wp-env-if-professional-astrea-57b77809`）は現在`stopped`。この環境が「やまだ」データを保持している可能性を検証するため、起動せずに済む安全な方法（既存のFixture DBバックアップSQLファイルの静的grep）で確認した（§5参照）— 結果、この永続環境は「やまだ」ではなく別の固定Fixture（「ASTREA行政書士事務所」）であることを確認したため、起動する必要はなかった。
- 本セッションからは`docker`コマンド自体を直接実行できず、生のコンテナ/ボリューム一覧を独立に取得することはできなかった（§2参照）。

## 5. Database Discovery（§4）

Construction 019専用のDBは存在しない（§3参照、破棄済み）。

参考として、リポジトリ直下`.fixture-backups/`に**別の**開発用Fixture DBのSQLバックアップが2点存在することを発見したが、いずれも「やまだ行政書士事務所」ではないことを内容から確認した:

| ファイル | 日時 | `blogname` | `siteurl` |
| --- | --- | --- | --- |
| `owner-fixture-pre-015g-20260829-201941.sql` | 2026-08-29 20:19 | `ASTREA行政書士事務所` | `http://localhost:8888` |
| `fixture-backup-20260830_010405.sql` | 2026-08-30 01:04 | `ASTREA行政書士事務所` | `http://localhost:8888` |

両ファイルとも「やまだ」「山田」の文字列を0件（`grep -ac`で確認）。これらは`.gitignore`のコメントが示す通りConstruction 015G（2026-08-29、Construction 019の1週間前）関連の**別の**Fixture DBスナップショットであり、Construction 019の「やまだ」データの代替にはならない。Git管理対象外（`.gitignore`により意図的に除外）。

## 6. Content Inventory（§5）

報告書§8より、実データ件数（Order記載の参考値と完全一致することを確認）:

| Type | 件数 | 備考 |
| --- | --- | --- |
| Professional | 1 | 山田太郎（代表者フラグON） |
| Service | 3 | — |
| CASE | 3 | うち1件のみ画像あり、2件は画像なし（§8参照） |
| Results | 3 | — |
| Price | 4 | — |
| FAQ | 4 | うち3件「重要」マーク |
| Voice | 3 | — |

個別のslug／status／menu order／taxonomy等の詳細は、DBが存在しないため証拠から取得不可。件数・構成の大枠のみ報告書テキストから確定できる。

## 7. Page / Navigation Inventory（§6）

- 固定ページ: 事務所概要／料金／お問い合わせ（Setup機能で自動生成、いずれもBlock Editorで実際にPublish済み）
- HOME: Setup機能で生成し固定フロントページとして設定
- Navigation: 「ASTREA 基本メニュー」1件（取扱業務・専門家紹介・FAQ・生成済み基本ページへのリンクを含む）
- Contact: 実際にContact Formから送信テスト実施（送信者「鈴木花子」、Inquiry ID 43「会社設立について相談したい」、`private`投稿として保存・非公開確認済み）

## 8. Visual Source of Truth / Image Forensics（§7〜§8）— 今回の最重要発見

42枚のスクリーンショットのうち、HOME全体（`04-home-1920.png`）と専門家プロフィール編集画面（`professional-metabox-expanded.png`）を実際に目視確認した。

**発見: 「代表者写真」（山田太郎のFeatured Image）と「対応事例#1の画像」は、いずれも実写真ではなく、単色（ネイビーグレー系）のプレースホルダー画像だった。**

- `professional-metabox-expanded.png`のBlock Editorサイドバー「Featured Image」パネルに表示されている画像は、単色の塗りつぶし矩形。これはテーマ側の「画像なし」フォールバック表示ではなく、**実際にMedia Libraryへ添付されたFeatured Imageそのもの**である（サイドバーのFeatured Imageパネルは、画像が未設定の場合はプレースホルダーアイコン付きの「Set featured image」ボタンを表示する仕様のため、実際に色矩形が表示されているということは、その色矩形自体が添付された画像ファイルであることを意味する）。
- `04-home-1920.png`のCASE #01カードの画像領域も同一の単色ネイビーグレー矩形。
- 報告書テキスト自体は「photo」「一件のみ画像あり」としか記載しておらず、それが実写真ではなく単色プレースホルダーだったことには言及していない。

**分類（§9 Provenance）: これら2点の画像は `F. source asset missing`（DB/uploads自体が破棄済みのため元ファイルは復元不可）に加え、仮に復元できたとしても実写真ではなかったことが視覚証拠から判明しているため `A/B相当のプレースホルダー` であり、公開LIVE DEMOの「写真入り」という前提を満たす実写真としては使用できない。**

その他の画像（Service一覧アイコン等）はグラフィックアイコン（フォルダアイコン等）であり写真ではない。CASE #02/#03は報告書記載通り画像なし。

## 9. Image / Media Forensics 詳細（§8）

| 項目 | 状態 |
| --- | --- |
| `wp-content/uploads` | 存在しない（環境ごと破棄） |
| Media Library attachment一覧 | 取得不可（DB破棄） |
| 確認できた添付ファイル数（スクリーンショットからの推定） | 2件（代表者写真・CASE#1画像）、いずれも単色プレースホルダー |
| リポジトリ内のasset/screenshots以外の画像資産 | `docs/research/screenshots/019/`の42枚のみ。これらはUI全体のキャプチャであり、個別の元画像ファイルではない |

## 10. Provenance / Publication Safety（§9）

| 対象 | 分類 | 理由 |
| --- | --- | --- |
| 代表者写真（山田太郎） | **F. source asset missing** | DB/uploads破棄。加えて実写真ではなく単色プレースホルダーだったことが判明（§8） |
| CASE#1画像 | **F. source asset missing** | 同上 |
| その他画像（Service/CASE#2-3/Results/Price/FAQ/Voice） | 該当なし | 報告書記載上、画像添付なし |

D/E/Fに分類された画像について、公開可否の判断は一切行っていない（Order §9の指示通り）。

## 11. PII / Fictionality Audit（§13）

報告書・スクリーンショットの範囲内で確認できた情報は、いずれも明確にfictionalな体裁であった:

- 代表者名: 山田太郎（きわめて一般的な日本語の仮名、実在人物の情報と混同する記載なし）
- 事務所住所: 東京都新宿区西新宿1-1-1 新宿タワー10F（報告書は明示的に「fictional address」と記載）
- 電話番号: 03-9876-5432（プレースホルダー的な連番パターン）
- 行政書士登録番号: 第98765432号（同上、プレースホルダー的な連番）
- 問い合わせ送信者: 鈴木花子（同じく一般的な仮名）
- Analytics/Search Console/APIキー: 報告書に設定した記載なし（GA4測定ID等はSetup画面上「任意」項目として未入力のまま）
- 通知メールアドレス: 「まだ設定されていない」と報告書に明記

**現存する証拠（報告書＋スクリーンショット）の範囲では、実在の個人情報・実際の連絡先情報が混入している痕跡は見つからなかった。** ただし、DB自体は確認不可能なため、この判定は「報告書テキスト＋スクリーンショットの目視範囲」に限定される。

## 12. Contact Form Safety（§14、現状確認のみ）

Construction 019当時の挙動（報告書§12より）:

- Contact送信は実際にメール送信されたとの記載はなし（「まだ通知メールアドレスが設定されていない」との報告書記載あり＝現状は非送信）
- 問い合わせデータは`private`投稿として保存され、一般公開されない
- 管理画面で未読バッジ・既読化・CSV書き出し・保持期間設定（自動削除）が確認された

将来のLIVE DEMOでこの機能を有効なまま公開する場合、実メール送信・個人情報の無期限保存を防ぐ安全設計（§14記載のdisabled/interception/notice等）が別途必要になる。**本Orderではこの設計の実施・変更は行っていない。**

## 13. Theme / Core Compatibility（§10）

| | Construction 019当時 | Current Stable |
| --- | --- | --- |
| Theme | 1.0.0-rc2 | 1.0.2 |
| Core | 1.0.0-rc2 | 1.0.1 |

DB自体が存在しないため、「当時のDB/contentをcurrent stableで表示した場合のvisual regression」は**検証不可（対象データが存在しないため技術的に評価不能）**。もし将来Owner判断により「やまだ」データを一から再構築する場合、最初からcurrent stable（Theme 1.0.2 / Core 1.0.1）に対して構築することになるため、この互換性問題は実質的に発生しない。

## 14. Local Visual Recovery Test（§11）

**実施不可。** Construction 019の環境自体が存在しないため、実機比較テストの対象がない。ブラウザでの追加検証は行っていない。

## 15. Migration Asset Inventory（§12）

| 分類 | 状態 |
| --- | --- |
| A. Database/content | **存在しない**（破棄済み） |
| B. uploads | **存在しない**（破棄済み、かつ実写真ではなかったことが判明） |
| C. Theme | 現行リポジトリのTheme（1.0.2）で代替可能。019当時のrc2コードそのものは不要（019は「製品コード変更0件」のため現行と同一） |
| D. Core | 同上（現行1.0.1で代替可能） |
| E. WordPress configuration | 記録なし（URL/ポート等証拠から確定不可） |
| F. permalink | 記録なし |
| G. Navigation | 「ASTREA 基本メニュー」の構成は報告書テキストから概要のみ判明（取扱業務・専門家紹介・FAQ・基本ページへのリンク） |
| H. Site Editor entities | Style Variation（Trust/Natural/Modern切替）が正しく機能したことのみ確認、entity自体は非存在 |
| I. demo-specific settings | 該当なし |
| J. required migration transformations | 該当なし（移植元DBが存在しないため） |

秘密情報（admin password、DB password、salts、APIキー等）は報告書・スクリーンショットのいずれにも記載されておらず、そもそも移植対象として検討する必要がない。

## 16. Live Demo Target — Plan Only（§16、計画のみ・未実行）

将来の公開先 `https://demo.project-if.jp/astrea/` について、If-Thema Modern Phase 9で確立したproduct-subpath architecture（`scripts/ops/demo-product-subpath-bootstrap.sh`等、project-ifリポジトリ側）を再利用する想定であることをOrder記載の通り確認した。**本Orderではbootstrap実行・VPS転送・DB作成・nginx変更のいずれも行っていない。**

前回Modern Phase 9で発生した「awk -vのエスケープ処理によりnginx location挿入が無言で失敗するバグ」は、project-ifリポジトリ側の`scripts/ops/demo-product-subpath-bootstrap.sh`および`demo-product-subpath-nginx-apply.sh`で`grep -Fxn`+`head`/`tail`方式に修正済みであることを、当時の該当コミット（project-ifリポジトリ側）で確認済み（本Order時点で再確認は行っていないが、修正はASTREA側の作業ではなくproject-if側repositoryの既存commitとして残っている）。次Order着手時に、astreaスラッグでの再利用前にこのスクリプトの現状を再監査することを推奨する。

## 17. Risks

- Construction 019のオリジナルDB/uploadsは技術的に復元不可能（破棄済み、バックアップの言及もなし）。
- 仮にゼロから「やまだ行政書士事務所」を再構築する場合、Owner受入時のコンテンツ文言（Hero文言、Service/CASE/Result/Price/FAQ/Voiceの具体的な文章）を完全に一致させることはできない（スクリーンショットから文字起こしすれば近似再現は可能だが、それは「回収」ではなく「再制作」になる）。
- 「写真入り」というOwnerの前提が、少なくとも現存する証拠の範囲では単色プレースホルダーであり、実写真は一度も使われていなかった可能性が高い。これは今後のLIVE DEMO計画において、実写真（またはライセンスの確認された代替画像）を新たに用意する必要があることを意味する。

## 18. Final Recovery Verdict（§17）

**D. VISUAL EVIDENCE ONLY**

Construction 019完成状態を示す視覚的証拠（42枚のスクリーンショット＋詳細な施工報告書テキスト）は十分に残っているが、実際の移植元となるDB・uploads・WordPress環境そのものは、Order自身の安全設計により意図的に破棄されており、一切残っていない。さらに、現存する視覚証拠を精査した結果、当時使用されていた「写真」は実写真ではなく単色プレースホルダー画像であったことが判明した。

高忠実度の技術的移植（SQLエクスポート・WXR・WP-CLIエクスポート等）は**不可能**である。次のステップは、Owner判断により以下のいずれかを選択することになる:

1. スクリーンショット・報告書を参考資料として、現行stable（Theme 1.0.2 / Core 1.0.1）上に「やまだ行政書士事務所」相当のコンテンツを**新規に再構築**する（完全な忠実再現ではなく、近似的な再現になる）
2. 実写真（またはライセンスの確認された代替画像）を新たに調達した上で、上記再構築を行う
3. 別のfictionalペルソナ・別の視覚コンセプトでLIVE DEMOを新規設計する

いずれの場合も、本Orderの範囲外であり、Ownerの承認を待つ。

---

**Status: AWAITING OWNER DECISION**

本Orderでは、ASTREA Theme/Core source・version・tag・GitHub Release・WordPress.org関連作業・ASTREA LIVE DEMOインフラ構築のいずれにも着手していません。次のConstructionへは自動的に進みません。
