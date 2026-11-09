#!/usr/bin/env python3
"""Vérification statique légère (sans PHP) : équilibre {} () [] des fichiers PHP/Blade et des directives Blade.
N'est PAS un substitut à `php -l` / `php artisan test`."""
import re, sys, pathlib
bad = 0
for p in pathlib.Path('.').rglob('*'):
    if p.suffix not in ('.php',) and not p.name.endswith('.blade.php') or 'vendor' in p.parts: continue
    s = p.read_text(encoding='utf-8')
    if p.name.endswith('.blade.php'):
        t = re.sub(r'\{\{.*?\}\}|\{!!.*?!!\}', '', s, flags=re.S)
        for a, b in [('@foreach', '@endforeach'), ('@forelse', '@endforelse'), ('@section(\'[^\']*\'\\s*\\)\\s*$', None)]:
            pass
        cnt = lambda x: len(re.findall(x, t))
        pairs = [(r'@foreach\b', r'@endforeach\b'), (r'@forelse\b', r'@endforelse\b'), (r'@push\b', r'@endpush\b'), (r'@unless\b', r'@endunless\b'), (r'@for\b', r'@endfor\b')]
        for a, b in pairs:
            if cnt(a) != cnt(b): print('BLADE', p, a, cnt(a), b, cnt(b)); bad += 1
        ifs = len(re.findall(r'@if\b', t)) ; ends = len(re.findall(r'@endif\b', t))
        if ifs != ends: print('BLADE @if', p, ifs, ends); bad += 1
        secs = len(re.findall(r"@section\('[^']*'\)\s*$|@section\('[^']*'\)\s*\n", t, flags=re.M)); es = len(re.findall(r'@endsection', t))
        if secs != es: print('BLADE @section', p, secs, es); bad += 1
        continue
    # PHP : retire chaînes et commentaires
    t = re.sub(r"('(?:\\.|[^'\\])*'|\"(?:\\.|[^\"\\])*\")|(//[^\n]*|/\*.*?\*/|\n[ \t]*#[^\n]*)", lambda m: "''" if m.group(1) else '', s, flags=re.S)
    for o, c in ['{}', '()', '[]']:
        if t.count(o) != t.count(c): print('PHP', p, o, t.count(o), c, t.count(c)); bad += 1
print('OK' if not bad else f'{bad} problème(s)'); sys.exit(1 if bad else 0)
