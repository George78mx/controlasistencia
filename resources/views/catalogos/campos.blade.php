@if($c === 'festivos')
    <div class="col-12"><label class="form-label">Fecha *</label><input type="date" name="fecha" value="{{ $r?->fecha?->toDateString() }}" class="form-control" required></div>
    <div class="col-12"><label class="form-label">Descripción *</label><input name="descripcion" value="{{ $r?->descripcion }}" class="form-control" required></div>
@elseif($c === 'tipos-incidencia')
    <div class="col-4"><label class="form-label">Clave *</label><input name="clave" value="{{ $r?->clave }}" maxlength="10" class="form-control text-uppercase" required></div>
    <div class="col-8"><label class="form-label">Nombre *</label><input name="nombre" value="{{ $r?->nombre }}" class="form-control" required></div>
    <div class="col-4"><label class="form-label">Color</label><input type="color" name="color" value="{{ $r?->color ?? '#6366f1' }}" class="form-control form-control-color w-100"></div>
    <div class="col-8 d-flex flex-column justify-content-end gap-1">
        <div class="form-check form-switch"><input type="checkbox" name="con_goce" value="1" class="form-check-input" id="goce{{ $r?->id }}" @checked($r?->con_goce ?? true)><label class="form-check-label" for="goce{{ $r?->id }}">Con goce de sueldo</label></div>
        <div class="form-check form-switch"><input type="checkbox" name="activo" value="1" class="form-check-input" id="act{{ $r?->id }}" @checked($r?->activo ?? true)><label class="form-check-label" for="act{{ $r?->id }}">Activo</label></div>
    </div>
@else
    <div class="col-12"><label class="form-label">Nombre *</label><input name="nombre" value="{{ $r?->nombre }}" class="form-control" required></div>
    <div class="col-12"><label class="form-label">Descripción</label><input name="descripcion" value="{{ $r?->descripcion }}" class="form-control"></div>
    <div class="col-12"><div class="form-check form-switch"><input type="checkbox" name="activo" value="1" class="form-check-input" id="act{{ $r?->id }}" @checked($r?->activo ?? true)><label class="form-check-label" for="act{{ $r?->id }}">Activo</label></div></div>
@endif
