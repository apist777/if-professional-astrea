# Construction 023-B — LIVE DEMO Final Visual Integration & Local Acceptance

「やまだ行政書士事務所」正式画像組み込み・最終ローカル検査

- Start: 2026-09-10 01:44:50 JST（実測）
- End: 2026-09-10 01:56:08 JST（実測）
- Duration: 0:11:18
- Modifier: Chloe
- Baseline: ASTREA Theme 1.0.2 / ASTREA Core 1.0.1（**無改変**）
- Mode: LOCAL FINALIZATION ONLY。VPS SSH・sudo・nginx変更・demo.project-if.jp変更・Project-if変更・本番deployのいずれも未実施。

## 0. Input Assets（§0、§2）

| 項目 | 内容 |
| --- | --- |
| 配置場所 | `docs/demo-assets/yamada-live-demo/images/`（Order記載の期待パスと一致） |
| Asset 01 | `astrea-demo-yamada-professional-portrait.png`、1616×973px、1,725,005 bytes、SHA-256 `f2b3cf5ad127533546317445408220967fb8acc10fc2b24986f098d39cfac17f` |
| Asset 02 | `astrea-demo-yamada-case-01-construction-permit.png`、1774×887px、1,812,903 bytes、SHA-256 `aaa7896d42a6fc4a50e534637080846ea1f6bd66f1e62b0fec4c53b451ebc22c` |
| Decode検証 | 両ファイルともPNGシグネチャ有効・IENDチャンクあり・IHDR記載寸法とファイル内容が一致。破損なし。 |

**ファイル名異常の報告**: `astrea-demo-yamada-case-01-construction-permit (2).png:Zone.Identifier`（25 bytes）という、対応する実データファイルが存在しないOS由来の孤立ファイルを検出した。これはWindows側でのダウンロード時に一時的に発生した命名衝突（`(2)`）の残骸で、実際の画像データは伴わない。実害はなく、既存プロジェクトでも同種の`:Zone.Identifier`副産物が随所で「無害な既存の無関係ファイル」として扱われてきた前例に倣い、削除せずそのまま保持した。

## 1. Asset Validation結果（§2）

| 項目 | Asset 01（代表者） | Asset 02（対応事例#1） |
| --- | --- | --- |
| 実測アスペクト比 | 1.661:1（目標1.7:1に対し約2.3%差） | 2.001:1（目標2:1にほぼ完全一致） |
| 解像度 | 十分（表示コンテナの最大幅648pxに対し1616px、Retina対応可） | 十分（表示コンテナの最大幅437pxに対し1774px） |
| 判定 | 目標比率と実質的に一致。crop許容範囲内 | 目標比率とほぼ完全一致 |

両画像とも「完全一致でなくてよい、十分な解像度・適切なaspect ratio・十分なvisual qualityがあればよい」という023-Bの基準を満たしていた。

## 2. Source / Web Asset分離・最適化（§3）

原本（PNG）は`docs/demo-assets/yamada-live-demo/images/`直下に無改変のまま保持。WordPress投入用に、PHPのGD拡張（WordPress Playground内蔵）で以下を実施——**原本を破壊しない新規ファイル生成**:

1. 各画像を目標アスペクト比（1.7:1 / 2:1）へセンタークロップ
2. JPEG品質85で書き出し（`images/web/`に保存）

| ファイル | 変換後サイズ | 削減率 |
| --- | --- | --- |
| `astrea-demo-yamada-professional-portrait.jpg` | 148,046 bytes | 元PNGの約8.6% |
| `astrea-demo-yamada-case-01-construction-permit.jpg` | 176,253 bytes | 元PNGの約9.7% |

メタデータ除去（EXIF等）はJPEG再エンコード（GDのimagejpeg()はEXIFを引き継がない）により自動的に達成。WordPress互換（標準JPEG、sRGB相当）を確認。

## 3. Professional Portrait Integration（§4）

- 対象: `astrea_professional`投稿「山田太郎」のFeatured Image
- 旧placeholder（attachment ID 6）は削除せず保持、新規attachment（ID 40）を作成しFeatured Imageを切り替え（`set_post_thumbnail()`、WordPress標準API）
- Desktop 1440px・Mobile 390pxで実機確認（Playwright、`docs/research/screenshots/023-B/E-professional-crop-1440.png`／`F-professional-crop-390.png`）:
  - 顔・頭頂部・顎とも切れていない
  - 手はデスク上に自然に収まり不自然なcropなし
  - 主役（人物）が明確、背景（観葉植物・書棚）はうるさくなく適度にボケている
  - ASTREAのタイポグラフィ（隣接する氏名・肩書テキスト）と重なり・干渉なし
  - `object-fit:cover`による意図しない極端なcropは発生していない（実測比率が目標に近いため）
- Alt text: 023-A仕様通り「代表 山田太郎（行政書士）のポートレート」を設定
- WordPress標準のfocal point調整UI・Theme CSS変更のいずれも不要だった（そのままのcenter-cropで適切に収まったため）

## 4. Case #1 Image Integration（§5）

- 対象: `astrea_case`投稿「建設業許可を初回申請で取得」のFeatured Image
- 旧placeholder（attachment ID 11）は保持、新規attachment（ID 41）を作成し切り替え
- 内容確認: 山田先生と相談者（後ろ姿、匿名）の打ち合わせシーン、ヘルメット・書類・図面が建設業許可案件を自然に補助——事例内容と整合
- Desktop/Mobile実機確認（`G-case-crop-1440.png`／`H-case-crop-390.png`）:
  - 山田先生の顔は自然な位置、切れなし
  - 相談者は後ろ姿のみで顔が写っておらず、不自然なcropという問題は発生していない
  - 書類上の文字は判読不能（意図通り、情報として依存していない）
  - 目立つAI生成の不自然な可読文字は確認されなかった
  - 実在企業・個人情報に見える表示はなし

## 5. Visual Consistency（§6）

2画像を実際のサイト上で比較した結果:

- 同一人物（山田太郎）であることが明確——顔立ち・髪型・スーツ・ネクタイが完全に一致
- 同一のoffice environment（同じ書棚・観葉植物・窓からの採光）
- 同一の光の方向・色温度（暖色系の自然光）
- Photographic realism（写実的、イラスト・3DCG調ではない）で統一
- ASTREA Trust Style（ネイビー＋ゴールド）との共存も自然（画像自体はネイビーに着色されていないが、周囲のUIとのコントラストで違和感なし）

**「別人に見える」「サイトとして違和感が強い」といった問題は確認されなかった。** 新規画像生成の必要はない。

## 6. Full HOME Regression（§7）

Playwright実機検証（1440px・390px、`docs/research/screenshots/023-B/A-home-1440.png`／`B-home-390.png`）:

| 確認項目 | 結果 |
| --- | --- |
| horizontal overflow | 0px（両ブレークポイント） |
| console error | 0件 |
| H1 | 一意 |
| broken image（`naturalWidth===0`） | 0件 |
| Header/Hero/Services/Cases/Results/Pricing/FAQ/Voice/Professional/Contact CTA/Footer | すべて正常表示、layout shiftや比率崩れなし |
| fictional disclosure導線 | 事務所概要ページに維持（後述§10） |

## 7. Lower Page Regression（§8）

| ページ | 結果 |
| --- | --- |
| Professional single（山田太郎） | 200、正式画像表示、`docs/research/screenshots/023-B/C-professional-single.png` |
| Case #1 single | 200、正式画像表示、`docs/research/screenshots/023-B/D-case-01-single.png` |
| 事務所概要（static Page） | 200、overflow 0、disclosure維持 |
| 料金 | 200、overflow 0 |
| お問い合わせ | 200、overflow 0 |

画像置換による他ページへの副作用は確認されなかった。

## 8. Mobile Image Inspection（§9、目視確認）

390pxでの視覚確認（数値検査だけでなくスクリーンショット目視、`E`〜`H`参照）:

- Portrait crop: 顔・肩とも適切な位置、余白バランス良好
- Case image crop: 人物・小道具（ヘルメット等）とも適切な位置
- text/image balance: HOME全体のセクション間の余白・カード高さに崩れなし
- 「overflow 0だからPASS」ではなく、目視でも違和感のない仕上がりであることを確認済み

## 9. Fictionality / Safety Recheck（§10）

- 事務所概要ページのfictional disclosure（Construction 023設置分）は維持されていることを確認（本文中に「架空の情報」を含む段落が1件存在）。
- 画像2点を目視確認した範囲で、実在企業ロゴ・判読可能な実在住所/電話番号・商標・個人を特定できる情報は含まれていない。

## 10. Contact Recheck（§11、regressionのみ、再送信テストは実施せず）

Construction 023で作成済みの問い合わせレコード（`astrea_inquiry`）の`post_status`が引き続き`private`であることをDBで確認。画像組み込み作業が原因で公開設定に変化した形跡はない。指示通り、必要以上の再送信テストは行っていない。

## 11. Reproducibility Package Update（§12）

`docs/demo-assets/yamada-live-demo/`を以下の通り更新:

- `yamada-demo-export.wxr` — 正式画像組み込み後の状態で再エクスポート（32 items、XML妥当性・秘密情報なしを確認済み）
- `images/` — Owner供給の正式画像原本2点（無改変で保持）
- `images/web/` — Web配信用最適化JPEG2点（新規）
- `scripts/integrate-final-images.php` — 正式画像組み込みスクリプト（新規保存）
- `README.md` — 現在の状態（placeholder→正式画像）を反映して更新
- `placeholder-images/` — Construction 023時点の旧placeholderは削除せず履歴として保持

## 12. Clean Rebuild Check（§13）— 独立した再現性の実証

既存のローカル環境（`~/astrea-live-demo-build/`）とは**完全に別の新規一時ディレクトリ・別ポート**で、保存済みパッケージのみを使って最初から再構築できることを検証した（既存環境は一切破壊していない）:

1. 新規WordPress環境を作成（同一のTheme/Coreファイルを参照マウント、DBは完全に新規）
2. `theme-core-activation-blueprint.json`でTheme/Core有効化
3. `build-content.php`でコンテンツ投入
4. `cleanup.php`で既定コンテンツ削除
5. `permalinks.php`でパーマリンク設定
6. `polish.php`で事務所概要・営業時間投入
7. `integrate-final-images.php`で正式画像2点を組み込み

**結果: 主環境と完全に同一のコンテンツ件数・同一の"正式版"添付ファイル・同一のFeatured Image切り替えを達成し、正式画像が実際にHTTP 200で配信されることを確認した。** 「今動いているサイトだけが完成している」のではなく、保存したパッケージから独立して完成状態を再現できることを実証した。

## 13. Product Integrity（§15）

```
$ git diff --stat -- theme/ core/
(no output)
```

ASTREA Theme source: **無変更**
ASTREA Core source: **無変更**

画像組み込みのためにTheme/Core変更が必要になった場面は一度もなかった（Featured Image自体はWordPress標準機構、crop/最適化はWordPress外部での画像前処理のみで完結）。STOPした事項はない。

## 14. Final Verdict（§19）

**A. LOCAL ACCEPTANCE CANDIDATE READY**

ただし、この判定はVPSへの移植を意味しない。次のステップはOwnerの明示的な承認を待つ。

## 15. Owner Acceptance Package（§20）

| # | 項目 | 参照 |
| --- | --- | --- |
| 1 | HOME Desktop | `docs/research/screenshots/023-B/A-home-1440.png` |
| 2 | HOME Mobile | `docs/research/screenshots/023-B/B-home-390.png` |
| 3 | Professional最終画像結果 | `docs/research/screenshots/023-B/C-professional-single.png`／`E`／`F` |
| 4 | Case #1最終画像結果 | `docs/research/screenshots/023-B/D-case-01-single.png`／`G`／`H` |
| 5 | Regression summary | 本報告§6〜§10（overflow 0・console error 0・broken image 0・disclosure維持・Contact private維持） |
| 6 | Theme/Core unchanged confirmation | 本報告§13（`git diff --stat`出力なし） |
| 7 | Reproducibility confirmation | 本報告§12（独立環境での完全再現を実証済み） |
| 8 | Remaining warnings | 孤立した`:Zone.Identifier`ファイル（無害、§0参照）。代表者写真の実測アスペクト比が目標より約2.3%わずかに異なる（視覚上は問題なし） |

---

**AWAITING OWNER: APPROVED FOR LIVE MIGRATION**

Ownerが明示的に承認するまで、VPS施工・Project-if導線追加へは進みません。
