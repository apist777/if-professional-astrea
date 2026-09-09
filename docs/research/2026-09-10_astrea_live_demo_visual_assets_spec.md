# Construction 023-A — LIVE DEMO Final Visual Assets Specification

「やまだ行政書士事務所」画像仕様確定

- Start: 2026-09-10 01:19:27 JST（実測）
- End: 2026-09-10 01:24:04 JST（実測）
- Duration: 0:04:37
- Modifier: Chloe
- Mode: **仕様確定のみ。画像生成・placeholder置換・local demo変更・Theme/Core変更・VPS施工のいずれも実施していない。**

## 0. 現状（§0）

Construction 023: LOCAL BUILD COMPLETE / AWAITING OWNER ACCEPTANCE（未変更）
Product baseline: ASTREA Theme 1.0.2 / ASTREA Core 1.0.1（無改変のまま）
対象: Professional portrait（山田太郎）／ Case #1 image の2点のみ。

## 1. 実測による重要な発見（§1）

Construction 023時点では、両画像とも**アスペクト比を実測せずに配置していた**ことが今回判明した。実際にローカルデモをPlaywrightで起動し、`getBoundingClientRect`相当の実測（Desktop 1440px・Mobile 390px）を行った結果：

| コンテナ | Desktop実測 | Mobile実測 | アスペクト比（CSS指定） |
| --- | --- | --- | --- |
| `.wp-block-astrea-representative-photo` | 647.7 × 381.0px | 390 × 229.4px | **1.7 : 1（横長）** |
| `.wp-block-astrea-case-item-media` | 437.3 × 218.7px | 326 × 163px | **2 : 1（横長）** |

両ブレークポイントとも、CSS上の`aspect-ratio`が絶対値のみ変えて厳密に固定されているため（`theme.json`の`styles.css`で`aspect-ratio:1.7`／`aspect-ratio:2/1`と明示）、**DesktopとMobileでcrop挙動に差は生じない**（相対的な見え方は完全に同一、絶対サイズのみスケールする）。

**重要な訂正**: Construction 023で実際に設置したplaceholderは、代表者写真が900×1200（3:4の**縦長**）、対応事例#1が1200×800（3:2）だった。しかし実際の表示コンテナはどちらも**横長**（代表者1.7:1、対応事例2:1）であり、`object-fit:cover`によって視覚的には正しくクロップ表示されていた（placeholderが単色だったため、この不一致は画面上では気づけなかった）。今回、コデちゃんへ渡す最終画像の仕様では、**表示コンテナと同じアスペクト比で生成する**ことを必須とし、この問題を解消する。

両画像とも共通仕様:
- WordPress attachment用途: **Featured Image**（アイキャッチ画像。`astrea_professional`／`astrea_case`投稿タイプ、`wp_get_attachment_image($id, 'large')`で出力）
- CSS: `width:100%;height:100%;object-fit:cover;display:block;`（コンテナのアスペクト比に強制フィット）
- Focal point: WordPress標準の`object-fit:cover`はセンタークロップが既定（focal point調整UIはASTREA未実装）。**被写体は画像中央に配置することが必須**（左右上下の端に寄せない）。

## 2. ASSET 01 — Professional Portrait（山田太郎）（§2）

- 使用section: HOME「代表者紹介」（`astrea/home-professional-teaser`パターン、`astrea/representative`動的ブロック）／ 専門家プロフィール個別ページ（`single-astrea_professional`テンプレート、同じ`wp_get_attachment_image`経由）
- Featured Imageとして使用（通常の本文画像ではない）
- Displayed aspect ratio: **1.7 : 1（横長）**
- Desktop container: 約648×381px（1440px viewport時）
- Mobile container: 390×229px（全幅表示に切替。Desktopと比率は同一）
- Recommended source aspect ratio: **1.7:1、正確に一致させる**（crop違和感を避けるため）
- Recommended pixel dimensions: **1700 × 1000px**（ratio 1.7ちょうど、Retina/高DPI表示に十分な解像度）

## 3. ASSET 02 — Case #1 Image（§3）

Construction 023で実際に作成した対応事例#1の内容（`build-content.php`／DBより確認、変更なし）:

- タイトル: 「建設業許可を初回申請で取得」
- 概要: 「必要書類の準備を丁寧に行い、初回申請でスムーズに建設業許可を取得したケースです。」
- 本文: 「新規に建設業を営むお客様より、建設業許可の取得についてご相談をいただきました。必要書類の準備段階から丁寧にヒアリングを行い、不備のない申請書類を作成した結果、初回申請でスムーズに許可を取得することができました。」
- 関連Service: 建設業許可申請

この案件内容（建設業許可申請）を視覚的に補助する画像とする。別業務への変更はしていない。

- 使用section: HOME「対応事例」カード（`astrea/home-cases-teaser`相当パターン、`astrea_case`一覧）／対応事例個別ページ
- Featured Imageとして使用
- Displayed aspect ratio: **2 : 1（横長）**
- Desktop container: 約437×219px、Mobile: 326×163px（比率同一）
- Recommended source aspect ratio: **2:1、正確に一致**
- Recommended pixel dimensions: **1600 × 800px**

## 4. Visual Consistency（§4）

両画像とも:
- 同一の光の方向・色温度（やや暖かい自然光、5000〜5500K相当）で統一
- Photographic realism（イラスト調・3DCG調にしない）
- ASTREA Trust Style（ネイビー`#102A43`＋ゴールドアクセント）と自然に共存する彩度・コントラスト（画像自体をネイビーに着色しない。あくまで自然な写真として、隣接するUIとのコントラストで統一感を出す）
- 同じ「都市部の個人〜小規模の士業事務所」という空気感（大企業の役員室のような過度な高級感を避ける）

## 5. Image Generation Policy（§5）確認事項

- 最終画像はProject-if ASTREA LIVE DEMO専用の新規生成アセット。第三者stock photoは使用しない（今回未生成、方針の確認のみ）。
- ロゴ・透かし・商標・読める社名・読める個人情報・誤解を招く資格表示は含めない。

## 6. File Specification（§6）

| # | 用途 | 推奨filename（生成原本） | 最終web形式 | Target dimensions | Aspect Ratio | 品質目標 | 想定最大ファイルサイズ | Alt text |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 01 | 代表者ポートレート | `astrea-demo-yamada-professional-portrait.png`（生成原本）→ `astrea-demo-yamada-professional-portrait.jpg`（配布用） | JPEG（原本）／WordPressアップロード後はCore側で追加変換なし。将来的にWebP配信を検討する場合はサーバ側（Project-if）で対応 | 1700×1000px | 1.7:1 | 高品質・自然な階調（過度な圧縮ノイズなし） | 500KB以下（JPEG q80目安） | 「代表 山田太郎（行政書士）のポートレート」 |
| 02 | 対応事例#1画像 | `astrea-demo-yamada-case-01-construction-permit.png`（生成原本）→ `.jpg`（配布用） | JPEG | 1600×800px | 2:1 | 高品質 | 400KB以下 | 「建設業許可申請に関する書類・打ち合わせのイメージ」 |

命名は本Order§6の例示パターン（`astrea-demo-<subject>`）をそのまま採用した。ASTREA Theme自体には既存の画像アセット命名規則が存在しない（現状インラインSVGアイコンのみで構成されているため）。

## 7. Exact Generation Brief（§7）— コデちゃんへそのまま渡せる仕様

```
--------------------------------
ASSET 01
Purpose: Featured image for the ASTREA LIVE DEMO representative (professional profile) section on HOME and the individual professional page.
Filename: astrea-demo-yamada-professional-portrait
Aspect Ratio: 1.7:1 (landscape) — MUST match exactly, do not crop after generation.
Target Size: 1700 x 1000 px
Composition: Subject occupies the center-right two-thirds of the frame, facing slightly toward camera-left (toward where body text would sit in the layout), with comfortable headroom above and below so the top ~15% and bottom ~15% of the frame can be safely cropped by a center-crop display without cutting the face or hands. Subject's face and shoulders must sit within the vertical-center 60% of the frame.
Subject: A fictional Japanese man in his late 30s to mid-40s, professional but approachable. NOT modeled on any real person.
Environment: A small, tidy administrative-scrivener (行政書士) office — a simple desk, a bookshelf with binders/law reference books softly out of focus in the background, warm natural light from a window. Not a large corporate boardroom, not a courtroom.
Wardrobe: A simple, well-fitted business suit or jacket, no flashy accessories, no visible logos or brand marks on clothing.
Expression: A calm, warm, approachable smile (not a stiff corporate headshot smile, not laughing).
Lighting: Soft natural window light from one side, warm color temperature (approx. 5000-5500K), gentle shadows, no harsh flash.
Camera: Waist-up to chest-up framing, eye-level angle, standard portrait lens look (shallow-to-moderate depth of field, background softly blurred but still legible as an office).
Background: Softly out-of-focus office interior as described above. No visible signage, no readable text, no company name, no logo.
Crop Safety: Keep the subject's face, both shoulders, and any visible hands fully inside the center 70% of the frame both horizontally and vertically.
Must Include: A single fictional Japanese professional adult, warm and approachable expression, soft office background.
Must Avoid: Any resemblance to a real/famous person; luxury/intimidating law-firm aesthetic; courtroom or judge imagery; any lawyer badge/pin; any readable qualification certificate, signage, company name, or logo; watermarks; AI-generated garbled text of any kind; distorted/unnatural hands; heavy beauty retouching; text of any kind rendered into the image (name/title must not appear in the image itself — WordPress displays those separately).
Alt Text: 代表 山田太郎（行政書士）のポートレート
--------------------------------

ASSET 02
Purpose: Featured image for Case Study #1 ("建設業許可を初回申請で取得" — obtaining a construction-business license on the first application) on HOME and the individual case page.
Filename: astrea-demo-yamada-case-01-construction-permit
Aspect Ratio: 2:1 (landscape) — MUST match exactly, do not crop after generation.
Target Size: 1600 x 800 px
Composition: A flat-lay or three-quarter angle desk scene, main subject matter positioned in the center 60% of the frame so a center-crop cannot lose it. Wide, calm negative space is acceptable at the left/right edges only.
Subject: Architectural/construction-related documents (e.g. a stack of application forms, a floor plan or blueprint, a hard hat resting on the desk beside the papers) suggesting a construction-business licensing consultation. No people required; if a person's hands appear reviewing documents, keep the face out of frame or fully anonymous (hands only).
Environment: A clean office desk setting, consistent with Asset 01's office (same light quality, same desk/bookshelf tone), NOT a construction site itself (the case is about the paperwork/permit process, not physical construction work).
Props: Application forms/documents (all text must be genuinely illegible — blurred, angled away from camera, or replaced with abstract placeholder marks — no real or fabricated company name, address, or personal data readable), a blueprint or floor plan (abstract/generic, not tied to any real building), optionally a hard hat as a visual cue for "construction," a pen.
Lighting: Same warm natural light as Asset 01 for visual consistency (approx. 5000-5500K, soft shadows).
Camera: Slightly elevated three-quarter angle or top-down flat-lay, moderate depth of field so the main documents/props are in focus and edges soften slightly.
Crop Safety: Keep all key props (documents, blueprint, hard hat if used) within the center 70% of the frame both horizontally and vertically.
Must Include: Construction-permit-application-related documents/props consistent with the case's actual content; visual tone consistent with Asset 01.
Must Avoid: Any readable text, company name, address, or personal data on documents; any real blueprint/building; a courtroom or judge imagery; a real construction site with heavy machinery (the case is about paperwork, not physical construction); logos or watermarks; AI-generated garbled text; people's faces (not needed for this asset).
Alt Text: 建設業許可申請に関する書類・打ち合わせのイメージ
--------------------------------
```

## 8. Owner Review Material（§8）

| # | 項目 | 内容 |
| --- | --- | --- |
| 1 | ASSET 01生成仕様 | 本報告§7参照 |
| 2 | ASSET 02生成仕様 | 本報告§7参照 |
| 3 | 現在placeholderが置かれている箇所 | HOME「代表者紹介」セクション／専門家プロフィール個別ページ、HOME「対応事例」カード#1／対応事例個別ページ |
| 4 | Desktop表示サイズ/比率 | Asset01: 約648×381px（1.7:1）／Asset02: 約437×219px（2:1）（1440px viewport時点） |
| 5 | Mobile表示サイズ/比率 | Asset01: 390×229px（1.7:1）／Asset02: 326×163px（2:1）（390px viewport時点、比率はDesktopと同一） |
| 6 | 推奨filename | `astrea-demo-yamada-professional-portrait` / `astrea-demo-yamada-case-01-construction-permit` |
| 7 | Alt text | 本報告§6・§7参照 |
| 8 | 現在のスクリーンショット | `docs/research/screenshots/023/01-home-1440.png`（HOME全体）／`docs/research/screenshots/023/03-professional-section.png`（代表者紹介セクション拡大） |

## 9. Do Not（§9）確認

画像生成・placeholder置換・local demo変更・Theme変更・Core変更・content変更・CSS変更・version bump・ZIP変更・VPS作業・nginx変更・Project-if変更・LIVE DEMO公開は、いずれも実施していない。

## 10. Completion Verdict（§12）

**VISUAL ASSET SPEC READY / AWAITING OWNER IMAGE GENERATION**

Owner承認なしで、画像生成・placeholder置換・VPS施工には進みません。
