@props(['filters'])

<div class="grid grid-cols-2 gap-3">
    <div>
        <label for="date_from" class="form-label">Dari Tanggal</label>
        <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-input">
    </div>
    <div>
        <label for="date_to" class="form-label">Sampai Tanggal</label>
        <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-input">
    </div>
</div>
