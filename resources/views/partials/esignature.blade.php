@if(($signer->esignature ?? null))
<img src="{{ asset('storage/' . $signer->esignature) }}" alt="Signature" style="height: 40px; margin: 0 auto; display: block;">
@else
Digitally Signed
@endif
