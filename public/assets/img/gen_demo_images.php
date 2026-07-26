<?php
/**
 * 时光邮局 示范信件配图生成器
 * 用 PHP GD 库生成 3 张不同主题的渐变背景图，作为首页示范信件的封面。
 * 纯 GD 实现，不依赖 FreeType/TTF 字体。
 * 运行：php public/assets/img/gen_demo_images.php
 * 生成后可删除本脚本。
 */

$dir = __DIR__;
$w = 1200;
$h = 800;

function genCover(string $filename, int $w, int $h, array $colors, string $scene): void
{
    $img = imagecreatetruecolor($w, $h);

    // 渐变背景（垂直渐变）
    [$r1, $g1, $b1] = $colors[0];
    [$r2, $g2, $b2] = $colors[1];
    for ($y = 0; $y < $h; $y++) {
        $ratio = $y / $h;
        $r = (int)($r1 + ($r2 - $r1) * $ratio);
        $g = (int)($g1 + ($g2 - $g1) * $ratio);
        $b = (int)($b1 + ($b2 - $b1) * $ratio);
        $color = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $w, $y, $color);
    }

    // 场景装饰
    if ($scene === 'seaside') {
        // 太阳光晕（多层）
        for ($i = 5; $i > 0; $i--) {
            $sunGlow = imagecolorallocatealpha($img, 255, 220, 150, 60 + $i * 10);
            imagefilledellipse($img, (int)($w * 0.5), (int)($h * 0.55), 200 + $i * 40, 200 + $i * 40, $sunGlow);
        }
        $sunCore = imagecolorallocate($img, 255, 245, 200);
        imagefilledellipse($img, (int)($w * 0.5), (int)($h * 0.55), 140, 140, $sunCore);

        // 海面反光
        $seaColor = imagecolorallocatealpha($img, 255, 180, 120, 50);
        imagefilledrectangle($img, 0, (int)($h * 0.7), $w, $h, $seaColor);
        // 海面波光线条
        $waveColor = imagecolorallocatealpha($img, 255, 230, 180, 30);
        for ($i = 0; $i < 30; $i++) {
            $wy = (int)($h * 0.7) + rand(0, (int)($h * 0.3));
            $wx = rand(0, $w);
            imageline($img, $wx, $wy, $wx + rand(30, 100), $wy, $waveColor);
        }

        // 两个剪影（坐在海边看日落）
        $silhouette = imagecolorallocate($img, 30, 20, 50);
        // 左侧人
        $lx = (int)($w * 0.42);
        imagefilledellipse($img, $lx, (int)($h * 0.82), 60, 70, $silhouette);
        imagefilledrectangle($img, $lx - 35, (int)($h * 0.82), $lx + 35, $h, $silhouette);
        // 右侧人
        $rx = (int)($w * 0.58);
        imagefilledellipse($img, $rx, (int)($h * 0.80), 60, 70, $silhouette);
        imagefilledrectangle($img, $rx - 35, (int)($h * 0.80), $rx + 35, $h, $silhouette);

    } elseif ($scene === 'umbrella') {
        // 路灯光晕
        for ($i = 5; $i > 0; $i--) {
            $lampGlow = imagecolorallocatealpha($img, 255, 210, 130, 60 + $i * 10);
            imagefilledellipse($img, 200, 150, 150 + $i * 30, 150 + $i * 30, $lampGlow);
        }
        $lampCore = imagecolorallocate($img, 255, 235, 180);
        imagefilledellipse($img, 200, 150, 60, 60, $lampCore);

        // 雨丝
        $rainColor = imagecolorallocatealpha($img, 255, 255, 255, 100);
        for ($i = 0; $i < 150; $i++) {
            $rx = rand(0, $w);
            $ry = rand(0, $h);
            imageline($img, $rx, $ry, $rx - 8, $ry + 30, $rainColor);
        }

        // 雨伞（半圆 + 伞柄）
        $umbColor = imagecolorallocate($img, 200, 70, 90);
        $cx = (int)($w / 2);
        $cy = (int)($h * 0.65);
        imagefilledarc($img, $cx, $cy, 280, 140, 180, 360, $umbColor, IMG_ARC_PIE);
        // 伞骨
        $boneColor = imagecolorallocate($img, 140, 40, 60);
        for ($a = 180; $a <= 360; $a += 30) {
            $rad = deg2rad($a);
            imageline($img, $cx, $cy, $cx + (int)(140 * cos($rad)), $cy + (int)(70 * sin($rad)), $boneColor);
        }
        // 伞柄
        imageline($img, $cx, $cy, $cx, $cy + 200, $boneColor);

        // 伞下人影
        $silhouette = imagecolorallocate($img, 25, 25, 50);
        imagefilledellipse($img, $cx, (int)($h * 0.80), 55, 65, $silhouette);
        imagefilledrectangle($img, $cx - 28, (int)($h * 0.80), $cx + 28, $h, $silhouette);

    } elseif ($scene === 'starry') {
        // 银河带（斜向椭圆）
        for ($i = 5; $i > 0; $i--) {
            $galaxyColor = imagecolorallocatealpha($img, 150 + $i * 10, 100 + $i * 15, 200, 60 + $i * 8);
            imagefilledellipse($img, (int)($w * 0.55), (int)($h * 0.3), 700 - $i * 50, 120 - $i * 10, $galaxyColor);
        }

        // 大量星星
        $starBright = imagecolorallocate($img, 255, 255, 255);
        $starDim = imagecolorallocatealpha($img, 255, 255, 255, 60);
        $starMid = imagecolorallocatealpha($img, 255, 255, 255, 30);
        for ($i = 0; $i < 400; $i++) {
            $sx = rand(0, $w);
            $sy = rand(0, (int)($h * 0.75));
            $r = rand(1, 4);
            $pick = rand(0, 10);
            if ($pick > 8) {
                imagefilledellipse($img, $sx, $sy, $r + 1, $r + 1, $starBright);
                // 亮星加十字光
                if ($r >= 3) {
                    imageline($img, $sx - 8, $sy, $sx + 8, $sy, $starMid);
                    imageline($img, $sx, $sy - 8, $sx, $sy + 8, $starMid);
                }
            } elseif ($pick > 4) {
                imagefilledellipse($img, $sx, $sy, $r, $r, $starDim);
            } else {
                imagesetpixel($img, $sx, $sy, $starMid);
            }
        }

        // 流星
        $meteorColor = imagecolorallocatealpha($img, 255, 255, 220, 20);
        $mx = (int)($w * 0.15);
        $my = (int)($h * 0.15);
        for ($i = 0; $i < 60; $i++) {
            $alpha = 127 - (int)($i * 2);
            if ($alpha < 0) $alpha = 0;
            $c = imagecolorallocatealpha($img, 255, 255, 220, $alpha);
            imagesetpixel($img, $mx - $i, $my + $i, $c);
        }

        // 山的剪影（多层，营造纵深）
        $mountain1 = imagecolorallocate($img, 35, 25, 70);
        $points1 = [[0, $h], [0, (int)($h * 0.78)], [(int)($w * 0.2), (int)($h * 0.65)], [(int)($w * 0.4), (int)($h * 0.78)], [(int)($w * 0.55), (int)($h * 0.7)], [$w, (int)($h * 0.75)], [$w, $h]];
        $flat1 = [];
        foreach ($points1 as $p) { $flat1[] = $p[0]; $flat1[] = $p[1]; }
        imagefilledpolygon($img, $flat1, count($points1), $mountain1);

        $mountain2 = imagecolorallocate($img, 15, 10, 40);
        $points2 = [[0, $h], [0, (int)($h * 0.88)], [(int)($w * 0.25), (int)($h * 0.8)], [(int)($w * 0.5), (int)($h * 0.85)], [(int)($w * 0.75), (int)($h * 0.82)], [$w, (int)($h * 0.88)], [$w, $h]];
        $flat2 = [];
        foreach ($points2 as $p) { $flat2[] = $p[0]; $flat2[] = $p[1]; }
        imagefilledpolygon($img, $flat2, count($points2), $mountain2);
    }

    // 底部暗角（提升 HTML 文字可读性）
    $vignette = imagecolorallocatealpha($img, 0, 0, 0, 80);
    imagefilledrectangle($img, 0, (int)($h * 0.82), $w, $h, $vignette);

    imagejpeg($img, $filename, 88);
    imagedestroy($img);
    echo "✓ 生成: " . basename($filename) . " (" . round(filesize($filename) / 1024, 1) . "KB)\n";
}

// ===== 1. 致我永远的盛夏 - 海边日落 =====
genCover(
    "{$dir}/demo_seaside.jpg",
    $w, $h,
    [[255, 170, 120], [150, 80, 130]],  // 暖橙 → 紫红
    'seaside'
);

// ===== 2. 时光里的我们 - 雨中撑伞 =====
genCover(
    "{$dir}/demo_umbrella.jpg",
    $w, $h,
    [[55, 65, 105], [25, 30, 65]],  // 深蓝 → 夜色
    'umbrella'
);

// ===== 3. 星空下的约定 - 山顶星空 =====
genCover(
    "{$dir}/demo_starry.jpg",
    $w, $h,
    [[15, 20, 65], [5, 5, 30]],  // 深紫蓝 → 黑
    'starry'
);

echo "\n全部完成。\n";
