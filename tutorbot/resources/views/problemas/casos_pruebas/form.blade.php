<div class="row mx-3">
    <div class="col">
        <div class="mb-3">
            <label for="entradas" class="form-label">Entradas</label>
            <textarea class="form-control @error('salidas') is-invalid @enderror" id="entradas" name="entradas" rows="5"></textarea>
            @error('entradas')
                <p class="text-danger text-xs pt-1"> {{ $message }} </p>
            @enderror
        </div>
    </div>
    <div class="col">
        <div class="mb-3">
            <label for="salidas" class="form-label">Salidas</label>
            <textarea class="form-control @error('salidas') is-invalid @enderror" id="salidas" name="salidas" rows="5"></textarea>
            @error('salidas')
                <p class="text-danger text-xs pt-1"> {{ $message }} </p>
            @enderror
        </div>
    </div>
    <div class="col">
        <div class="mb-3">
            <label for="puntos" class="form-label">Puntos</label>
            <input type="number" class="form-control @error('puntos') is-invalid @enderror" id="puntos" name="puntos" placeholder="Ej. 5">
            @error('puntos')
                <p class="text-danger text-xs pt-1"> {{ $message }} </p>
            @enderror
        </div>
        <button class="btn btn-primary" type="submit">Añadir</button>
        <button type="button" class="btn bg-outline-primary" data-bs-toggle="modal" data-bs-target="#ejemplo_modal">
            Ver Ejemplo
        </button>
    </div>
</div>