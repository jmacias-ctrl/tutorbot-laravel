@extends('layout_plataforma.app', ['title_html' => $certamen->nombre, 'title' => 'Certamen - ' . $certamen->nombre, 'breadcrumbs'=>[["nombre"=>"Evaluaciones", "route"=>route('certamenes.listado')], ["nombre"=>$certamen->nombre]]])

@section('content')
    <div class="container-fluid py-3 px-4">
        @include('components.alert')
        <div class="row mb-3">
            <div class="col-sm-8 col-xs-12">
                <div class="card border-danger overflow-auto" style="height:40rem">
                    <div class="card-header">
                        Desripción
                    </div>
                    <div class="card-body p-4 text-wrap" id="body_markdown">
                        {!! Str::markdown($certamen->descripcion, [
                            'html_input' => 'strip',
                            'allow_unsafe_links' => false,
                        ]) !!}
                    </div>
                </div>
            </div>
            <div class="col-sm-4 col-xs-12">
                <div class="card border-danger" style="height:40rem">
                    <div class="card-body px-3">
                        <div class="row px-5">
                            @if(isset($res_certamen) && $res_certamen->finalizado==false)
                                <a class="btn btn-primary text-nowrap btn-block {{ $certamen->disponibilidad ? '' : 'disabled' }}" href="#"
                                    role="button">{{ 'Volver al Certamen' }}</a>
                            @else
                                <a class="btn btn-primary text-nowrap btn-block {{ $certamen->disponibilidad ? '' : 'disabled' }}" href="{{route('certamenes.iniciar_resolucion', ['id_certamen'=>$certamen->id])}}"
                                    role="button">{{ $certamen->disponibilidad ? 'Resolver Certamen' : 'Certamen No Disponible' }}</a>
                            @endif
                        </div>
                        @can('ver informe del certamen')
                            <div class="row px-5 mt-2 mb-2">
                                <a class="btn btn-outline-secondary text-nowrap btn-sm btn-block"
                                    href="#"
                                    role="button">Ver Informe del Certamen</a>
                            </div>
                        @endcan
                        @can('editar certamen')
                            <div class="row px-5 mt-2 mb-2">
                                <a class="btn btn-outline-secondary text-nowrap btn-sm btn-block"
                                    href="{{route('certamen.editar', ['id'=>$certamen->id])}}"
                                    role="button">Editar Certamen</a>
                            </div>
                        @endcan
                        <hr>
                        <h6 class="ms-3 mt-3"><strong>Información:</strong></h6>
                        <ul class="list-group mt-3">
                            <li class="list-group-item"><strong>Curso:</strong>
                                {{$certamen->curso->nombre}}
                            </li>
                            @if (isset($certamen->fecha_inicio))
                                <li class="list-group-item"><strong>Fecha de Inicio:</strong> {{ $certamen->fecha_inicio }}
                                </li>
                            @endif
                            @if (isset($certamen->fecha_termino))
                                <li class="list-group-item"><strong>Fecha de Termino:</strong>
                                    {{ $certamen->fecha_termino }}</li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        var table = document.querySelector("#body_markdown table")
        if (table != null) {
            var table_body = table.querySelector("tbody")
            table.classList.add("table")
            table.classList.add("table-bordered")
            table.classList.add("table-hover")
            table.classList.add("mt-3")
            table.style.width = "auto"
            table_body.classList.add("table-group-divider")
        }
    </script>
@endpush