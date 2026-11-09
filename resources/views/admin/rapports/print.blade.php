<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>{{ $rap['titre'] }}</title>
<style>body{font-family:DejaVu Sans,Arial,sans-serif;font-size:11px;color:#111}h1{font-size:16px;color:#0b1f3a;margin:0}.sub{color:#555;margin-bottom:10px}
table{width:100%;border-collapse:collapse}th{background:#0b1f3a;color:#fff;text-align:left;padding:5px;font-size:10px}td{border-bottom:1px solid #ddd;padding:4px}.tot{margin:8px 0}</style></head>
<body @if(empty($pdf)) onload="window.print()" @endif>
<h1>UPL PRESENCE — Université Protestante de Lubumbashi</h1><div class="sub">{{ $rap['titre'] }} · généré le {{ now()->format('d/m/Y H:i') }}</div>
@if($rap['type']!=='jour')<div class="tot">Jours attendus : <b>{{ $rap['totaux']['attendus'] }}</b> · Présences : <b>{{ $rap['totaux']['presents'] }}</b> · Retards : <b>{{ $rap['totaux']['retards'] }}</b> · Absences : <b>{{ $rap['totaux']['absences'] }}</b></div>@endif
<table><thead><tr>@foreach($rap['entetes'] as $h)<th>{{ $h }}</th>@endforeach</tr></thead><tbody>@foreach($rap['lignes'] as $l)<tr>@foreach($l as $c)<td>{{ $c }}</td>@endforeach</tr>@endforeach</tbody></table></body></html>
