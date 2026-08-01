<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Sample catalogue, so a fresh clone has something to look at.
 *
 * Cloning a starter template and landing on an empty grid tells you nothing
 * about whether it works. Six products, written in both languages, exercise the
 * public site, the ordering, the draft/published split and the translation
 * fallback all at once.
 *
 * Every body here is written in the subset RichText allows, so it survives the
 * sanitiser untouched and demonstrates what the editor can actually produce.
 */
class DemoContentSeeder extends Seeder
{
    /**
     * @var array<int, array{slug: string, status: string, en: array{name: string, summary: string, body: string}, zh: array{name: string, summary: string, body: string}}>
     */
    private const PRODUCTS = [
        [
            'slug' => 'precision-steel-bearing',
            'status' => Product::PUBLISHED,
            'en' => [
                'name' => 'Precision Steel Bearing',
                'summary' => 'A high-tolerance bearing for continuous industrial duty.',
                'body' => '<h2>Overview</h2><p>A precision bearing rated for continuous duty, with a <strong>hardened steel</strong> race and a <em>sealed</em> cage that keeps contamination out without regular relubrication.</p><ul><li>Bore 20 mm, outer diameter 47 mm</li><li>Rated to 12,000 rpm</li><li>Sealed on both sides</li></ul><h3>Handling</h3><blockquote>Store below 40°C and away from direct sunlight.</blockquote>',
            ],
            'zh' => [
                'name' => '精密鋼珠軸承',
                'summary' => '適用於工業連續運轉的高精度軸承。',
                'body' => '<h2>產品概述</h2><p>採用<strong>硬化鋼</strong>內外環與<em>密封</em>保持架,適用於連續運轉,無需頻繁補充潤滑。</p><ul><li>內徑 20 mm,外徑 47 mm</li><li>額定轉速 12,000 rpm</li><li>雙面密封</li></ul><h3>保存方式</h3><blockquote>請存放於 40°C 以下,避免陽光直射。</blockquote>',
            ],
        ],
        [
            'slug' => 'linear-motion-rail',
            'status' => Product::PUBLISHED,
            'en' => [
                'name' => 'Linear Motion Rail',
                'summary' => 'Ground rail and carriage for repeatable positioning.',
                'body' => '<h2>Overview</h2><p>A ground rail paired with a recirculating carriage, for axes that need to return to the same place every time.</p><ul><li>Repeatability within 5 μm</li><li>Available in 300, 600 and 1200 mm lengths</li><li>Corrosion-resistant coating</li></ul>',
            ],
            'zh' => [
                'name' => '線性滑軌',
                'summary' => '研磨滑軌與滑塊,提供可重複的定位精度。',
                'body' => '<h2>產品概述</h2><p>研磨滑軌搭配循環式滑塊,適用於每次都必須回到同一位置的軸向。</p><ul><li>重複定位精度 5 μm 以內</li><li>提供 300、600、1200 mm 三種長度</li><li>表面經防蝕處理</li></ul>',
            ],
        ],
        [
            'slug' => 'harmonic-gear-reducer',
            'status' => Product::PUBLISHED,
            'en' => [
                'name' => 'Harmonic Gear Reducer',
                'summary' => 'Zero-backlash reduction for robot joints.',
                'body' => '<h2>Overview</h2><p>A strain wave reducer with effectively zero backlash, which is what makes it the usual choice for articulated robot joints.</p><ul><li>Ratios from 50:1 to 160:1</li><li>Rated torque 42 Nm</li><li>Hollow shaft for cable routing</li></ul>',
            ],
            'zh' => [
                'name' => '諧波減速機',
                'summary' => '零背隙減速機,適用於機器人關節。',
                'body' => '<h2>產品概述</h2><p>諧波齒輪結構,背隙趨近於零,這也是關節型機器人普遍採用它的原因。</p><ul><li>減速比 50:1 至 160:1</li><li>額定扭矩 42 Nm</li><li>中空軸設計,便於走線</li></ul>',
            ],
        ],
        [
            'slug' => 'servo-drive-controller',
            'status' => Product::PUBLISHED,
            'en' => [
                'name' => 'Servo Drive Controller',
                'summary' => 'Single-axis drive with EtherCAT and pulse input.',
                'body' => '<h2>Overview</h2><p>A single-axis servo drive that speaks EtherCAT and accepts pulse input, so it drops into both new installations and older machines.</p><ul><li>200 W to 1 kW</li><li>EtherCAT, pulse and analogue input</li><li>Auto-tuning on first run</li></ul>',
            ],
            'zh' => [
                'name' => '伺服驅動器',
                'summary' => '單軸驅動器,支援 EtherCAT 與脈波輸入。',
                'body' => '<h2>產品概述</h2><p>單軸伺服驅動器,同時支援 EtherCAT 與脈波輸入,因此新設備與舊機台都能導入。</p><ul><li>功率 200 W 至 1 kW</li><li>支援 EtherCAT、脈波與類比輸入</li><li>首次運轉自動調校</li></ul>',
            ],
        ],
        [
            'slug' => 'vibration-damping-mount',
            'status' => Product::PUBLISHED,
            'en' => [
                'name' => 'Vibration Damping Mount',
                'summary' => 'Isolates machine frames from floor vibration.',
                'body' => '<h2>Overview</h2><p>A bonded rubber and steel mount that isolates a machine frame from floor-borne vibration, and keeps the frame level while doing it.</p><ul><li>Load range 50–400 kg per mount</li><li>Natural frequency 8 Hz</li><li>Height adjustable by 12 mm</li></ul>',
            ],
            'zh' => [
                'name' => '減振腳座',
                'summary' => '隔絕機台與地面之間的振動傳遞。',
                'body' => '<h2>產品概述</h2><p>橡膠與鋼件接合的腳座,隔絕來自地面的振動,同時維持機台水平。</p><ul><li>單顆承載範圍 50–400 kg</li><li>自然頻率 8 Hz</li><li>高度可調整 12 mm</li></ul>',
            ],
        ],
        [
            // Left as a draft on purpose: it should be visible in the admin
            // panel and absent from the public site, which is the quickest way
            // to see that the publishing rule is real.
            'slug' => 'magnetic-encoder-ring',
            'status' => Product::DRAFT,
            'en' => [
                'name' => 'Magnetic Encoder Ring',
                'summary' => 'Not published yet — visible in the admin panel only.',
                'body' => '<h2>Overview</h2><p>A draft, kept here so the difference between the admin catalogue and the public site is visible without changing anything.</p>',
            ],
            'zh' => [
                'name' => '磁性編碼器環',
                'summary' => '尚未發佈 —— 只會出現在後台。',
                'body' => '<h2>產品概述</h2><p>這是一筆草稿,留在這裡讓「後台看得到、官網看不到」的差別不必動任何設定就能看見。</p>',
            ],
        ],
    ];

    public function run(): void
    {
        foreach (array_values(self::PRODUCTS) as $position => $demo) {
            $product = Product::updateOrCreate(
                ['slug' => $demo['slug']],
                [
                    'status' => $demo['status'],
                    'sort_order' => $position,
                    'published_at' => $demo['status'] === Product::PUBLISHED ? now() : null,
                ],
            );

            foreach (['en' => $demo['en'], 'zh-TW' => $demo['zh']] as $locale => $values) {
                $product->translations()->updateOrCreate(['locale' => $locale], $values);
            }
        }
    }
}
