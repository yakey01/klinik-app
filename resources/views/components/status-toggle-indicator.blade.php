@if($status_aktif)
    <div class="status-indicator active">
        <div class="status-dot active"></div>
        <span>Status: AKTIF</span>
    </div>
@else
    <div class="status-indicator inactive">
        <div class="status-dot inactive"></div>
        <span>Status: NONAKTIF</span>
    </div>
@endif