@foreach($c['champs'] as $f)
  @php [$name,$label,$type] = $f; $val = old($name, $m ? ($type==='json' ? ($m->$name ? json_encode($m->$name, JSON_UNESCAPED_UNICODE) : '') : $m->$name) : ($type==='checkbox' ? true : null));
       if($type==='time' && $val) $val = substr($val,0,5); @endphp
  <div class="col-md-{{ in_array($type,['textarea','json']) ? 12 : 6 }}">
  @if($type==='checkbox')
    <div class="form-check mt-4"><input type="hidden" name="{{ $name }}" value="0"><input class="form-check-input" type="checkbox" name="{{ $name }}" value="1" id="f_{{ $name }}" @checked($val)><label class="form-check-label" for="f_{{ $name }}">{{ $label }}</label></div>
  @else
    <label class="form-label">{{ $label }}</label>
    @if($type==='select')<select name="{{ $name }}" class="form-select" required><option value="">—</option>@foreach($opts[$name] as $k=>$v)<option value="{{ $k }}" @selected((string)$val===(string)$k)>{{ $v }}</option>@endforeach</select>
    @elseif($type==='textarea' || $type==='json')<textarea name="{{ $name }}" rows="{{ $type==='json'?2:3 }}" class="form-control {{ $type==='json'?'font-monospace':'' }}">{{ $val }}</textarea>
    @else<input type="{{ $type }}" name="{{ $name }}" value="{{ $val }}" class="form-control" @if($type==='number') step="any" @endif>@endif
  @endif
  </div>
@endforeach
