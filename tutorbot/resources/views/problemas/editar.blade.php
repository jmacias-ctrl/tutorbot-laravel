@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100', 'title_url' => 'Editar Problema'])

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Editar Problema'])
    <div id="alert">
        @include('components.alert')
    </div>
    <div class="container-fluid py-4">
        <form role="form" method="POST" action="{{ route('problemas.update', ['id' => $problema->id]) }}"
            enctype="multipart/form-data" onsubmit="event.preventDefault();submitFormEditar('{{'el problema '.$problema->nombre}}')" id="editarForm">
            @csrf
            <div class="card">
                <div class="card-body">
                    @include('problemas.form')
                    <input type="submit" class="btn btn-primary" value="Editar">
                    <a href="{{route('problemas.index')}}" class="btn btn-outline-primary">Volver</a>
                </div>
            </div>
        </form>
        @include('layouts.footers.auth.footer')

    </div>
@endsection

@push('js')
    <script src="{{ asset('assets/js/alertas_administracion.js') }}"></script> 
    <script type="module">
        const checkbox = document.getElementById('sql')
        const lenguajes = document.getElementById("lenguajes");
        const sql_file = document.getElementById("sql_file");
        const archivos_adicionales = document.getElementById("archivos_adicionales")
        const fecha_inicio = flatpickr("#fecha_inicio", {enableTime: true,
            dateFormat: "d-m-Y H:i",minDate: new Date(),}); 
        const fecha_termino = flatpickr("#fecha_termino", {enableTime: true,
            dateFormat: "d-m-Y H:i", minDate: new Date(),}); 
        fecha_inicio.setDate(Date.parse("{{old('fecha_inicio', $problema->fecha_inicio)}}"))
        fecha_termino.setDate(Date.parse("{{old('fecha_termino', $problema->fecha_termino)}}"))
        const editor = new Editor({
            el: document.querySelector('#editor'),
            height: '600px',
            initialEditType: 'markdown',
            placeholder: 'Ingrese el enunciado del problema',
            initialValue: `{{ isset($problema) ? old('body_problema', $problema->body_problema) : old('body_problema') }}`,
        })
        document.querySelector('#editarForm').addEventListener('submit', e => {
            document.querySelector('#body_problema').value = editor.getMarkdown();
        });

        checkbox.addEventListener('change', (event) => {
            if (event.currentTarget.checked) {
                lenguajes.disabled = true;
                sql_file.classList.remove("d-none");
            } else {
                sql_file.classList.add("d-none");
                archivos_adicionales.value = ""
                lenguajes.disabled = false;
            }
        })
    </script>
@endpush
