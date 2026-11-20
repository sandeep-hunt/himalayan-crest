{{--
  View-only text of a translated language (only English is edited).
  The hidden input keeps the stored translation in the form; it is replaced by the
  new Google translation when Save is pressed (admin.js).
  $name  input name, e.g. title[te]     $value  translated text     $hasEnglish  English is not empty
  $code  language code                  $rtl    right-to-left script
--}}
<input type="hidden" name="{{ $name }}" value="{{ $value }}" />
<div class="tr-view {{ $value === '' || $value === null ? 'is-empty' : '' }}" lang="{{ $code }}" dir="{{ ! empty($rtl) ? 'rtl' : 'auto' }}" data-tr-view>{{ ($value === '' || $value === null) ? ($hasEnglish ? 'Not translated yet. Press Save in English to translate it.' : '—') : $value }}</div>
