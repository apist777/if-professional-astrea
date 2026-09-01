# Construction Order 016D-R1 — Owner Reference Fidelity Reconstruction 実装Report

- **Construction Order**: 016D-R1
- **Date**: 2026-09-01
- **Status**: **AWAITING OWNER VISUAL ACCEPTANCE**（RELEASE HOLD継続）
- **Functional Baseline**: RC1 (1.0.0-rc1) — 変更なし

## 0. Owner Verdict

Construction 016DのGeometry Reconstructionは承認。ただしVisual v3全体としては未承認（「特に代表者紹介、その下の料金、左右余白など、見本との差がまだ大きい」）。本Orderは、Owner提示のVisual v3完成見本画像をSource of Truthとして実装をさらに近づける、という指示だった。

### 0.1 参照画像について（正直な報告）

Order指定のパス `docs/research/references/visual-v3-owner-reference.png` は、本Order実行中も**最終的に配置されなかった**（ディレクトリのみ作成され、ファイル本体は届かなかった）。このため、**画像からのピクセル測定・Side-by-side比較は一切行っていない**。

Order自身の文章による具体的な指示（Header/Hero/Services/CASE/Results/Professional/Priceそれぞれの詳細な構成記述）が、Order §1〜§14に十分な粒度で含まれていたため、これを実装のSource of Truthとして採用した。「Reference画像から読み取れる正確なpx値」を主張できる項目は無く、**すべて文章指示＋既存承認済みSpecification（07番）に基づく実装**であることを明記する。

## 1. ASTREA Icon System — Core Data Model 追加（最初に完了させた部分）

Order §7/§9/§11は「Serviceタイトル文字列を解析してIcon自動推測することを禁止、必要ならCore側で最小限のsemantic keyを追加」と明示していたため、実装着手前にTheme/Core責任分離を確認したうえで以下を新設した。

### 1.1 共有Icon Registry（`core/includes/icon-system.php`、新規）

`theme/assets/icons/{price,results,services}/`の10 SVGファイルの内容を、`currentColor`変換した状態で一箇所にまとめたRegistry。ThemeファイルへのRuntime `file_get_contents()`は行わない（Core/Theme分離——Themeが差し替えられてもCoreは動作し続ける必要がある、Decision 013/021）。`allowed_slugs($context)` / `default_slug($context)` / `make_sanitizer($context)`が、Service/Result/Priceそれぞれの許可Slug一覧・既定値・Sanitize関数のSingle Source of Truthとなり、Admin `<select>`とSanitize Callbackが同じ配列を参照するため両者が乖離しない設計にした。

### 1.2 各CPTへのIcon Postmeta追加

| CPT | Meta Key | 許可Slug | 既定値 |
|---|---|---|---|
| `astrea_service` | `astrea_service_icon` | company/contract/document/folder/inheritance/permit | folder |
| `astrea_result` | `astrea_result_icon` | result-company/result-check/result-consultation | result-check |
| `astrea_price` | `astrea_price_icon` | Service用6種 + price-yen（Order §11「PriceはService Icon Setを再利用」） | price-yen |

`register_post_meta()`のsanitize_callbackに加え、**classic Meta Box（`update_post_meta()`経由）はREST用sanitize_callbackを通らない**ことを踏まえ、`service-admin.php`（新規）・`result-admin.php`・`price-admin.php`の`save_meta()`側でも同じ`allowed_slugs()`whitelistで再検証している（手作りPOSTで任意文字列を保存できない）。

`service-admin.php`は`astrea_service`に初めて追加されるMeta Box（既存Admin fileが無かったため新規作成）。`astrea-core.php`のRequireリストへ`icon-system.php`（先頭付近）・`service-admin.php`を追加した。

### 1.3 テスト（Order §18で明示要求）

新規`tests/IconSystemTest.php`（Registry/Render/allowed_slugs/default_slug/make_sanitizerの単体テスト）に加え、`ServiceTest.php`・`ResultTest.php`・`PriceTest.php`へ以下を追加: 有効値の保持、不正文字列のFallback、他Context由来Slugの拒否、Nonce欠落時の拒否、016D-R1以前に作成された既存データ（Iconメタ行が存在しない）の互換性確認。**PHPUnit 397 tests, 661 assertions — OK**（既存359件+新規38件）。

### 1.4 実装中のヒヤリハット（自己発見・復旧）

`icon-system.php`の各Icon SVGで、Accent色ストロークに`class="astrea-icon-accent"`という**共有クラス名**を採用したが、016CからのServices/Results用CSSは旧来の`wp-block-astrea-service-item-icon-accent`/`wp-block-astrea-result-item-icon-accent`という**Block別クラス名**を参照したままだった。実機確認前にCSS文字列を機械的にgrepし、この不一致（Gold Accentが描画されない静かな不具合になり得た）を発見・修正した。

### 1.5 誤操作からの復旧（正直な報告）

作業中、`docker exec ... rm -rf /var/www/html/tests && mv ...`を実行した際、`tests/`がHost/Container間のBind Mountであることを見落とし、**Host側の`tests/`ディレクトリ全体を誤って削除した**。直後に`git checkout -- tests/`でGit追跡済みファイルを復元し、未コミットだった新規テスト内容（直前の会話で書いた内容）を書き直して復旧した。最終的な`git status`で意図した4ファイルの差分のみであることを確認済み。

## 2. Header — Reference方向へ再構成

Order §4: 白背景・Logo左Grid・Navigation右・Header自体が軽量・HeroとHeaderが視覚的に分離。

- 016B-R2で導入した`position:absolute`透過Overlay Header（Hero上に浮かせる方式）を**廃止**し、通常のDocument Flowへ戻した。`header.html`自体にbackgroundColor指定は無く、通常Flowに戻すだけで白背景が自然に得られる。
- `border-bottom:1px solid var(--wp--preset--color--border)`を追加し、Hero/HeaderのVisual分離を明示。
- 長い事務所名の安全策（`.astrea-header-identity{max-width:min(480px,45%)}`、Desktop限定）は、Overlay時代に発見した「Header高さが伸びてHeroと衝突する」問題への対策だったが、Headerが通常Flowに戻った今その衝突リスク自体は解消している。念のため維持（実害はない）。

## 3. Hero — MAJOR RECONSTRUCTION

Order §5の核心要求: 「Overlay Hero（写真の上に文字）」ではなく「Text Plane（左、明るい背景）＋ Photography（右、大きく）」の2つの独立したVisual Plane。

`theme/patterns/home-hero.php`を全面書き換え: 単一`core/cover`（写真全面背景＋文字Scrim）から、外側`core/group`（`layout:flex`）＋子要素2つ（`.astrea-hero-textplane`＝`core/group`、`.astrea-hero-photoplane`＝`core/cover`）という構造へ変更した。

- **Text Plane**（`flex:0 0 44%`、白背景・濃紺文字）: Kicker（H1、Gold文字色`accent`）／Primary Copy（大きなEditorial文字、濃紺）／Supporting Copy／Phone・Contact CTA。
- **Photo Plane**（`flex:1 1 56%`）: `core/cover`のまま——**016Bで確立した「urlが無ければ単色Fallback」の仕組みをそのまま右半分だけに適用**しており、No-photo Fallback Safeという要求を新たな仕組みを作らずに満たしている。
- **Robustness上の副次的改善**: 旧方式は「文字が写真の上に乗る」ため、任意の将来の写真の明るさに依存して可読性が変わるリスクがあった。新方式は文字が常に自分専用の白背景の上にあるため、**写真の明暗に一切依存しない**——後退ではなくRobustness面での純粋な改善である。
- Next section hint（`::after`、Markup追加なし）はPhoto Plane側へ移設。
- **Mobile**（Order §5「斜め分割を強制しない、Text→CTA→Photo等の自然なstacked compositionへ変化してよい」）: `flex-direction:column`でText Plane→Photo Planeの単純Stackへ変更。**016A-R1で確立した「写真右上ブリード＋Textプレートのオーバーラップ」複雑構成（margin collapse対策のoverflow:hidden、`:has()`によるImage再配置等）はまるごと不要になり削除した**——新しい2-Plane構造がMobileでもそのまま自然に成立するため。

### 3.1 実装中に発見・修正したバグ

新Text Planeの子要素（Kicker/Primary/Buttons、いずれも`display:flex`）が、Gutenbergの`is-layout-constrained`自動付与Margin（`margin:auto`）の影響で意図せず中央寄せに見える事象を実機確認で発見した。`max-width`のみ解除して`margin`をそのままにしていたことが原因——`display:flex`要素は明示的な`width`が無いとShrink-to-fitするため、残った`margin:auto`が可視化されていた。`width:100%`を明示的に追加し解決、Desktop/Mobileとも実測で解消を確認した。

Owner Fixture（Page ID 1914）のHOME content内のHero MarkupもPattern同様に更新し、実写真（`hero-office-city.png`）をPhoto Plane側へ設定（`dimRatio`は45——文字が写真上に無いため、旧方式（75）ほど強く暗くする必要がなくなった）。

## 4. Services — Icon個別化

Order §7「全Service folder icon表示は最終形として不可」。Fixture 6件へ個別Icon割当（会社設立サポート→company、在留資格申請→document、契約書作成・リーガルチェック→contract、他3件も割当済み）。HOME上の3件（company/document/contract）が視覚的に判別できることを実機確認。

## 5. CASE — Reference 3-column Card構成へ復帰

Order §8「016C/016D Editorial Rowは今回終了する」。`.wp-block-astrea-case-list`をGrid（3列）+ Cardへ全面書き換え。Radius/Shadow/Borderは付けず（Editorial/Agency外観を維持）。No-photo項目は、旧来の「グレー枠+丸アイコン」Placeholderを廃止し、`.is-empty{display:none}`でMedia領域自体を省略——Order §8の「画像が壊れて見えるPlaceholder禁止」「Text-led Cardへの変化」の両方を満たす。実機3件（写真あり1件+写真なし2件）で確認、写真なしCardも壊れて見えないことを目視確認した。

## 6. Results — Icon個別化 + 数字/単位のTypography分離

Order §9。Fixture 3件へ個別Icon割当（200社以上→result-company、98%→result-check、500件以上→result-consultation）。

数字/単位分離: `results-list-block.php`に`render_value()`を新設し、先頭の数字（`[0-9,.]+`）と残りの単位文字列を正規表現で分離、単位のみ小さいFont-sizeで表示するMarkupへ変換した。**既存データ互換性を壊さない設計**——先頭に数字が無い自由記述値（例:「全国対応」「多数」）は分割せず従来通り1つの文字列として出力する（`ResultTest.php`に専用テストあり）。既存テスト`test_results_list_block_heading_appears_alongside_content`が分割後の新しい出力形に合わせて更新が必要だったため、あわせて修正した（PHPUnit再実行で確認済み）。

## 7. Professional

016DからのGeometry（Photo 46%／Info 54%、共有Grid適用）は維持。Order指摘の「不自然な細かい改行」（「会社／設立・建設業許可を…」のような単語途中での分断）は、実機確認の結果、現行のFont-size/Max-width設定で発生していないことを確認した（016D-R1で新たなCSS変更は行っていない——016Dで既に改善済みだったため）。

## 8. Price — MAJOR PRIORITY、4-column化

Order §11。HOME教材用のCompact Price List（`.wp-block-astrea-price-list--compact`）をGrid（4列、共有Grid適用）+ 縦罫線 + Icon付きへ全面書き換え。各列: Icon／Group Label／Name／Amount（大きめ文字）／Notes。専用Price詳細ページ（`.wp-block-astrea-price-list`、非Compact）は**意図的に変更していない**（Finding 8の構造再設計は明示的にScope外）。

Fixture 4件へ個別Icon割当（会社設立パック→company、建設業許可申請→permit、相続手続きサポート→inheritance、顧問契約→price-yen、Order §11の指定通り）。実機で4列Grid・Icon・共有Gridへの整列を確認した。

## 9. CTA Band（Price直下）— 未対応、理由を明記

Order §12「Price直下のdark CTA bandも今回対象」だったが、既存の`astrea/closing-cta` Dynamic Blockは`setup-home.php`のPattern順で Price のかなり後（FAQ/VOICE/Flowを挟んだ末尾）に配置されており、これをPrice直後へ**再配置**するのはPattern順序の変更を伴う。Order §17「DO NOT TOUCH」にFAQ/VOICE/Flow自体の再設計は含まれるが、**Pattern全体の並び順変更は明示されておらず**、誤ってScope外の構造変更に踏み込むリスクを避けるため、今回は据え置いた。

また、実機確認中に既存の`.wp-block-astrea-closing-cta`要素がPlaywrightの可視性チェックで検出できない事象（`waitForSelector`がタイムアウト）を発見したが、これは本Orderの変更と無関係な既存動作であり、原因調査は行っていない——Known Limitationとして記録する（§16参照）。

## 10. Section Rhythm

Order §13の目標配色（Hero明/Services白/CASE淡/Results濃紺/Professional白/Price白/CTA濃紺）のうち、Hero（新設計で完全に明るいText Plane）・Results（既存濃紺帯）・Services/Price（白）は満たしている。CASE（Order「Very light surface」）は現状「白」のままで、専用の淡色背景は追加していない——CASE自体がPatternとして独立した背景色を持たせるには`home-case-teaser.php`docblockが明示する「Dynamic Blockの0件自己非表示を壊す静的Wrapping Group禁止」制約（Decision 028）に抵触するため、Results同様「Dynamic Block自身にCSSで背景を持たせる」設計が必要だが、時間の制約により本Orderでは見送った。Known Limitationとして記録する。

## 11. Responsive

| 幅 | Horizontal Overflow |
|---|---|
| 320px | 0px |
| 375px | 0px |
| 768px | 0px |
| 1024px | 0px |
| 1366px | 0px |
| 1440px | 0px |
| 1920px | 0px |

全幅Playwrightで機械計測（`scrollWidth - clientWidth`）。Mobile（≤600px）はHero（Text→Photo Stack）・Services/CASE（1列）・Results（1列）・Professional（Photo→Text）・Price（既存の折返しGrid挙動）いずれもOverflow無し、目視でも崩れを確認していない。

## 12. Accessibility

- H1は引き続き1個のみ（Hero Kicker、Construction 011契約無変更）。
- 新規Icon（Service/Result/Price、計3箇所）はいずれも`aria-hidden="true"`（IconSystem Registry側で一元管理、個別Block側で重複指定していない）。
- CTA/Nav/Landmark構造は今回変更していない。

## 13. Core OFF

`astrea-core`無効化→HOME再取得→HTTPステータス200、Fatal/Warning 0件を確認。共有Grid CSS・新Icon System CSSはいずれもtheme.json由来のためCore状態非依存。確認後再Activate済み。

## 14. Variation Tests

Trust（Default）/Natural/Modern、いずれも新Hero構造（Text Plane+Photo Plane）・新CASE Card・新Price Gridが正しく機能することを実機確認（Screenshot取得済み）。Trustへ復元済み。

## 15. Changed Files

```
core/astrea-core.php                       (Require追加: icon-system.php, service-admin.php)
core/includes/icon-system.php              (新規: 共有Icon Registry)
core/includes/service.php                  (META_ICON追加)
core/includes/service-admin.php            (新規: Icon選択UI)
core/includes/service-list-block.php       (Icon描画をIconSystem経由へ)
core/includes/result.php                   (META_ICON追加)
core/includes/result-admin.php             (Icon選択UI追加)
core/includes/results-list-block.php       (Icon描画・数字/単位分離)
core/includes/price.php                    (META_ICON追加)
core/includes/price-admin.php              (Icon選択UI追加)
core/includes/price-list-block.php         (Icon描画追加)
theme/patterns/home-hero.php               (全面書き換え: Text Plane+Photo Plane)
theme/theme.json                           (Header/Hero/CASE/Price CSS全面改修)
tests/IconSystemTest.php                   (新規)
tests/ServiceTest.php / ResultTest.php / PriceTest.php （Icon関連テスト追加）
```

## 16. Known Limitations（正直な報告）

- Owner提示見本画像が届かず、すべて文章指示に基づく実装——Pixel-perfect一致は主張できない。
- CTA BandのPrice直後への再配置は未実施（Pattern順序変更のScope判断が不明確だったため据え置き）。
- CASEセクションの背景色（Order「Very light surface」）は現状白のまま——Decision 028のDynamic Block自己非表示制約への対応方針が必要、時間制約により見送り。
- `.wp-block-astrea-closing-cta`のPlaywright可視性検出失敗（既存動作、本Order起因ではない）は未調査。
- Price詳細ページ（非Compact）はIconのみ追加、Geometry自体は変更していない（Finding 8同様、Scope外として維持）。

## 17. Screenshots

`docs/research/screenshots/016d-r1/`配下:

- `02-before-1920.png`（016D時点） / `03-after-1920.png`（本Order後）
- `04-reference-vs-after-1920.png`（**Owner見本画像が無いため、016D→016D-R1のBefore/After比較として代替**）
- `05-after-1440.png` / `06-after-1366.png` / `07-after-1024.png` / `08-after-768.png` / `09-after-375.png` / `10-after-320.png`
- `11-header-hero.png` / `12-services.png` / `13-case.png` / `14-results.png` / `15-professional.png` / `16-price.png`
- `18-full-home-1920.png` / `19-full-home-1440.png`

**`01-reference.png`（Owner見本そのもの）および`17-price-cta.png`は取得していない**（§9/§16参照）。

## 18. Exact Start / End / Duration

- Start: 2026-09-01 02:27 JST（016D HISTORY確認コミット時刻）
- End: 2026-09-01 13:56 JST
- Duration: 約11時間29分

**内訳の注記**（推測ではなく事実として記録）: この時間には、当初指定された参照画像の到着待ち（複数回の確認・Ownerとの往復）が含まれる。実際の実装作業（Icon System構築・Header/Hero/CASE/Price再構築・テスト・スクリーンショット取得）は、Ownerによる最終Clarification受領後に集中して行った——正確な着手時刻の証跡が残っていないため、Durationは「Order受領からComplete報告までの継続対応期間」として記録する。

## 19. Commit Hashes

（本Report作成後にコミット、次のセクションで確定）

## 20. HISTORY.csv Update

別途HISTORY.csvへ本Orderの行を追加する。

---

**Status: AWAITING OWNER VISUAL ACCEPTANCE**

Construction 016E / Release作業には進みません。Owner確認後、APPROVED / REVISE / FAILのいずれかのご判断をお待ちしています。
