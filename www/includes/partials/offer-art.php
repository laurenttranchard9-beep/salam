<?php
declare(strict_types=1);

/** Petites illustrations « étiquette » des formules. Les couleurs viennent des variables CSS de la carte. */
function offer_art(string $key): string
{
    $ink = '#24130d';
    switch ($key) {
        case 'carte':
            $rows = '';
            foreach ([78, 100, 122, 144] as $i => $y) {
                $w = [52, 40, 48, 36][$i];
                $rows .= '<rect x="62" y="' . $y . '" width="' . $w . '" height="7" rx="3.5" fill="' . $ink . '"/>'
                    . '<rect x="120" y="' . ($y - 1) . '" width="20" height="9" rx="4.5" class="art-acc"/>';
            }
            $qr = '';
            $cells = ['1110111', '1010101', '1110111', '0001000', '1101011', '0110110', '1011101'];
            foreach ($cells as $r => $line) {
                foreach (str_split($line) as $c => $on) {
                    if ($on === '1') {
                        $qr .= '<rect x="' . (104 + $c * 5) . '" y="' . (164 + $r * 5) . '" width="5" height="5"/>';
                    }
                }
            }
            return '<svg viewBox="0 0 200 240" aria-hidden="true"><g transform="rotate(-6 100 120)">'
                . '<rect x="44" y="26" width="116" height="186" rx="16" fill="#fff" stroke="' . $ink . '" stroke-width="4"/>'
                . '<rect x="62" y="46" width="62" height="12" rx="6" class="art-acc2"/>'
                . $rows
                . '<g fill="' . $ink . '">' . $qr . '</g>'
                . '<rect x="62" y="166" width="30" height="7" rx="3.5" fill="' . $ink . '" opacity=".25"/>'
                . '<rect x="62" y="180" width="22" height="7" rx="3.5" fill="' . $ink . '" opacity=".25"/>'
                . '</g></svg>';

        case 'gestion':
            $toggles = '';
            foreach ([[70, true], [108, true], [146, false]] as [$y, $on]) {
                $toggles .= '<rect x="72" y="' . $y . '" width="36" height="7" rx="3.5" fill="' . $ink . '"/>'
                    . '<rect x="72" y="' . ($y + 12) . '" width="22" height="6" rx="3" fill="' . $ink . '" opacity=".3"/>'
                    . '<rect x="112" y="' . ($y - 1) . '" width="28" height="16" rx="8" ' . ($on ? 'class="art-acc2"' : 'fill="#e6ddd3"') . ' stroke="' . $ink . '" stroke-width="3"/>'
                    . '<circle cx="' . ($on ? 132 : 120) . '" cy="' . ($y + 7) . '" r="5" fill="#fff" stroke="' . $ink . '" stroke-width="3"/>';
            }
            return '<svg viewBox="0 0 200 240" aria-hidden="true"><g transform="rotate(5 100 120)">'
                . '<rect x="56" y="22" width="100" height="196" rx="20" fill="#fff" stroke="' . $ink . '" stroke-width="4"/>'
                . '<rect x="88" y="32" width="36" height="8" rx="4" fill="' . $ink . '"/>'
                . $toggles
                . '<rect x="72" y="182" width="68" height="18" rx="9" fill="' . $ink . '"/>'
                . '</g>'
                . '<g transform="rotate(-10 150 70)"><rect x="118" y="52" width="66" height="30" rx="15" fill="#fff" stroke="' . $ink . '" stroke-width="4"/>'
                . '<text x="151" y="72" text-anchor="middle" font-family="Bricolage Grotesque, sans-serif" font-weight="800" font-size="15" fill="' . $ink . '">12,50 €</text></g>'
                . '</svg>';

        case 'site':
            $scallops = '';
            for ($i = 0; $i < 6; $i++) {
                $x = 34 + $i * 22;
                $scallops .= '<path d="M' . $x . ' 78h22v8a11 11 0 0 1-22 0Z" ' . ($i % 2 ? 'fill="#fff"' : 'class="art-acc2"') . ' stroke="' . $ink . '" stroke-width="4" stroke-linejoin="round"/>';
            }
            return '<svg viewBox="0 0 200 240" aria-hidden="true">'
                . '<rect x="42" y="86" width="116" height="126" fill="#fff" stroke="' . $ink . '" stroke-width="4"/>'
                . '<path d="M40 40h120l8 38H32Z" class="art-acc" stroke="' . $ink . '" stroke-width="4" stroke-linejoin="round"/>'
                . '<text x="100" y="66" text-anchor="middle" font-family="Bricolage Grotesque, sans-serif" font-weight="800" font-size="17" fill="' . $ink . '">RESTO</text>'
                . $scallops
                . '<rect x="58" y="118" width="46" height="94" rx="4" fill="#f6ede3" stroke="' . $ink . '" stroke-width="4"/>'
                . '<circle cx="95" cy="166" r="3.5" fill="' . $ink . '"/>'
                . '<rect x="114" y="118" width="30" height="40" rx="4" fill="#bfe4ff" stroke="' . $ink . '" stroke-width="4"/>'
                . '<g transform="rotate(-8 81 134)"><rect x="63" y="126" width="36" height="16" rx="4" class="art-acc2" stroke="' . $ink . '" stroke-width="3"/>'
                . '<text x="81" y="138" text-anchor="middle" font-family="Unbounded, sans-serif" font-weight="700" font-size="7.5" fill="#fff">OUVERT</text></g>'
                . '</svg>';

        default: // sur mesure : traduction et idées
            return '<svg viewBox="0 0 200 240" aria-hidden="true">'
                . '<g transform="rotate(-7 80 90)"><path d="M36 58a16 16 0 0 1 16-16h66a16 16 0 0 1 16 16v34a16 16 0 0 1-16 16H70l-20 16v-16a16 16 0 0 1-14-16Z" fill="#fff" stroke="' . $ink . '" stroke-width="4" stroke-linejoin="round"/>'
                . '<text x="85" y="84" text-anchor="middle" font-family="Bricolage Grotesque, sans-serif" font-weight="800" font-size="20" fill="' . $ink . '">Bonjour</text></g>'
                . '<g transform="rotate(6 124 160)"><path d="M70 146a16 16 0 0 1 16-16h66a16 16 0 0 1 16 16v34a16 16 0 0 1-14 16v16l-20-16H86a16 16 0 0 1-16-16Z" class="art-acc2" stroke="' . $ink . '" stroke-width="4" stroke-linejoin="round"/>'
                . '<text x="119" y="172" text-anchor="middle" font-family="Bricolage Grotesque, sans-serif" font-weight="800" font-size="20" fill="#fff">Hello!</text></g>'
                . '<path d="M160 40c1 9 6 14 15 15-9 1-14 6-15 15-1-9-6-14-15-15 9-1 14-6 15-15Z" fill="#fff" stroke="' . $ink . '" stroke-width="3" stroke-linejoin="round"/>'
                . '<path d="M40 176c.7 6 4 9.3 10 10-6 .7-9.3 4-10 10-.7-6-4-9.3-10-10 6-.7 9.3-4 10-10Z" fill="#fff" stroke="' . $ink . '" stroke-width="3" stroke-linejoin="round"/>'
                . '</svg>';
    }
}
