<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Problemas;
use App\Models\Cursos;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use App\Models\EvaluacionSolucion;
use App\Models\LenguajesProgramaciones;
use App\Models\EnvioSolucionProblema;
use App\Models\SolicitudRaLlm;
use Illuminate\Database\Query\Builder;
use Carbon\Carbon;
class InformeController extends Controller
{
    public function index_problema(Request $request){
        $problema = Problemas::with("cursos")->find($request->id);
        if(!isset($problema)){
            return redirect()->route('problemas.index')->with('error', 'El problema que estás tratando de acceder no existe.');
        }
        $cursos_usuarios = auth()->user()->cursos()->select('cursos.id')->get()->pluck('id')->toArray();
        $cursos = $problema->cursos()->select('disponible.cantidad_resueltos', 'disponible.cantidad_intentos', 'disponible.cant_retroalimentacion_solicitada','cursos.id as id_curso','cursos.nombre', 'cursos.codigo')->whereIn('cursos.id', $cursos_usuarios)->get();
        return view("informes.problemas.index", compact("problema", "cursos"));
    }
    public function ver_envios_problema(Request $request){
        $problema = Problemas::find($request->id_problema);
        if(!isset($problema) && !Cursos::where("id", "=", $request->id_curso)->exists()){
           return redirect()->route('informes.problemas.index', ["id"=>$request->id_problema])->with("Error", "El curso o problema no existe");
        }
        if(!auth()->user()->cursos()->where('cursos.id', '=', $request->id_curso)->exists()){
           return redirect()->route('informes.problemas.index', ["id"=>$request->id_problema])->with("Error", "No tienes acceso para ver éste informe");
        }
        $ultima_evaluacion = DB::table('evaluacion_solucions')
        ->select('resultado', 'id_envio', 'estado')
        ->where('estado', '=', 'Rechazado')
        ->orWhere('estado', '=', 'En Proceso')
        ->orWhere('estado', '=', 'Error')
        ->orderBy('updated_at','DESC')
        ->groupBy('id_envio', 'resultado', 'estado');
        $envios = DB::table("envio_solucion_problemas")
        ->join('resolver', 'resolver.id', '=', 'envio_solucion_problemas.id_resolver')
        ->join('cursa', 'cursa.id', '=', 'envio_solucion_problemas.id_cursa')
        ->join('problemas', 'problemas.id', '=', 'resolver.id_problema')
        ->leftJoin('casos__pruebas', 'casos__pruebas.id_problema', '=', 'problemas.id')
        ->join("users", "users.id", "=", "cursa.id_usuario")
        ->join("lenguajes_programaciones", "lenguajes_programaciones.id", "=", "resolver.id_lenguaje")
        ->leftJoinSub($ultima_evaluacion, 'ultima_evaluacion', function (JoinClause $join){
            $join->on('envio_solucion_problemas.id', '=', 'ultima_evaluacion.id_envio');
        })
        ->select("envio_solucion_problemas.token","cursa.id_curso", "problemas.nombre", "problemas.codigo","resolver.id_problema","users.firstname", "users.lastname", "users.rut", 'envio_solucion_problemas.token', 'envio_solucion_problemas.cant_casos_resuelto','envio_solucion_problemas.puntaje','lenguajes_programaciones.nombre as nombre_lenguaje', 'envio_solucion_problemas.solucionado', 'envio_solucion_problemas.inicio', 'envio_solucion_problemas.termino', 'ultima_evaluacion.resultado', 'ultima_evaluacion.estado', DB::raw('count(casos__pruebas.id) as total_casos'))
        ->where("cursa.id_curso", "=", $request->id_curso)
        ->whereNull('id_certamen')
        ->where("problemas.id", "=", $request->id_problema)
        ->whereNotNull("termino")
        ->groupBy("envio_solucion_problemas.token","cursa.id_curso", "problemas.nombre", "problemas.codigo","resolver.id_problema","users.firstname", "users.lastname", "users.rut", 'envio_solucion_problemas.token', 'envio_solucion_problemas.cant_casos_resuelto','envio_solucion_problemas.puntaje','lenguajes_programaciones.nombre', 'envio_solucion_problemas.solucionado', 'envio_solucion_problemas.inicio', 'envio_solucion_problemas.termino', 'ultima_evaluacion.resultado', 'ultima_evaluacion.estado')
        ->orderBy("envio_solucion_problemas.created_at", "DESC")
        ->orderBy("users.firstname", "ASC");
        if(isset($request->id_usuario)){
            $envios = $envios->where('users.id', '=', $request->id_usuario);
        }
        $envios = $envios->get();
        return view("informes.problemas.envios", compact("envios", "problema"));
    }

    public function ver_informe_problema(Request $request){
        if(!Problemas::where('problemas.id', '=', $request->id_problema)->exists() || !Cursos::where("cursos.id", "=", $request->id_curso)->exists()){
            return redirect()->route('informes.problemas.index', ["id"=>$request->id_problema])->with("Error", "El curso o problema no existe");
        }
        if(!auth()->user()->cursos()->where('cursos.id', '=', $request->id_curso)->exists()){
            return redirect()->route('informes.problemas.index', ["id"=>$request->id_problema])->with("Error", "No tienes acceso para ver éste informe porque no estás asignado al curso correspondiente.");
        }
        $estadistica_estados = EvaluacionSolucion::join('envio_solucion_problemas', 'envio_solucion_problemas.id', '=', 'evaluacion_solucions.id_envio')->join('resolver','resolver.id', '=', 'envio_solucion_problemas.id_resolver')->join('cursa','cursa.id', '=', 'envio_solucion_problemas.id_cursa')->select('resultado')->where('resolver.id_problema', '=', $request->id_problema)->where('cursa.id_curso', '=', $request->id_curso)->get()->countBy('resultado')->toArray();
        $cantidad_solucionados = EnvioSolucionProblema::join('resolver','resolver.id', '=', 'envio_solucion_problemas.id_resolver')->join('cursa','cursa.id', '=', 'envio_solucion_problemas.id_cursa')->where("id_problema", "=", $request->id_problema)->where('id_curso', '=', $request->id_curso)->where('solucionado', '=', true)->count();
        $info_usuarios_envios = DB::table('envio_solucion_problemas')
        ->join('resolver','resolver.id', '=', 'envio_solucion_problemas.id_resolver')->join('cursa','cursa.id', '=', 'envio_solucion_problemas.id_cursa')
        ->select(DB::raw('max(envio_solucion_problemas.cant_casos_resuelto) as max_casos_resueltos'), DB::raw('max(envio_solucion_problemas.puntaje) as max_puntaje'), 'cursa.id_usuario', DB::raw('max(envio_solucion_problemas.solucionado) as solucionado'), DB::raw('count(envio_solucion_problemas.id) as cant_intentos'), DB::raw('count(solicitud_ra_llms.id) as cant_retroalimentacion'), DB::raw('max(envio_solucion_problemas.created_at) as fecha_maxima'))
        ->leftJoin('solicitud_ra_llms', 'solicitud_ra_llms.id_envio', '=', 'envio_solucion_problemas.id')
        ->where('resolver.id_problema', '=', $request->id_problema)
        ->where('cursa.id_curso', '=', $request->id_curso)
        ->whereNotNull('envio_solucion_problemas.termino')
        ->groupBy('cursa.id_usuario');
        $envios = DB::table('users')
        ->join('cursa', 'cursa.id_usuario', '=', 'users.id')
        ->join('envio_solucion_problemas', 'envio_solucion_problemas.id_cursa', '=', 'cursa.id')
        ->join('resolver','resolver.id', '=', 'envio_solucion_problemas.id_resolver')
        ->joinSub($info_usuarios_envios, 'info_usuario_envios', function (JoinClause $join){
            $join->on('info_usuario_envios.fecha_maxima', '=', 'envio_solucion_problemas.created_at');
        })
        ->select('cursa.id_curso','resolver.id_problema', 'envio_solucion_problemas.inicio', 'envio_solucion_problemas.termino','users.id as id_usuario','users.rut', 'users.firstname', 'users.lastname','info_usuario_envios.max_casos_resueltos', 'info_usuario_envios.max_puntaje' ,'info_usuario_envios.solucionado', 'info_usuario_envios.cant_intentos', 'info_usuario_envios.cant_retroalimentacion')
        ->groupBy('cursa.id_curso','resolver.id_problema', 'envio_solucion_problemas.inicio', 'envio_solucion_problemas.termino','users.id','users.rut', 'users.firstname', 'users.lastname', 'info_usuario_envios.max_casos_resueltos', 'info_usuario_envios.max_puntaje', 'info_usuario_envios.cant_intentos', 'info_usuario_envios.cant_retroalimentacion','info_usuario_envios.solucionado')
        ->where('resolver.id_problema', '=', $request->id_problema)
        ->where('cursa.id_curso', '=', $request->id_curso)
        ->whereNotNull('envio_solucion_problemas.termino')
        ->whereNull('id_certamen')
        ->orderBy('envio_solucion_problemas.solucionado', 'DESC')
        ->get()->map(function ($envio){
            if(isset($envio->termino)){
                $envio->diferencia = Carbon::parse($envio->termino)->diffInSeconds(Carbon::parse($envio->inicio));
                $envio->diferencia = gmdate('H:i:s', $envio->diferencia);
            }
            return $envio;
        });
        $lenguajes_estadistica = DB::table('lenguajes_programaciones')
        ->join('resolver', 'resolver.id_lenguaje', '=', 'lenguajes_programaciones.id')
        ->join('envio_solucion_problemas', 'envio_solucion_problemas.id_resolver', '=', 'resolver.id')
        ->join('cursa', 'cursa.id', '=', 'envio_solucion_problemas.id_cursa')
        ->select('lenguajes_programaciones.nombre')
        ->where('resolver.id_problema', '=', $request->id_problema)
        ->where('cursa.id_curso', '=', $request->id_curso)
        ->whereNotNull('envio_solucion_problemas.termino')
        ->get()->countBy('nombre')->toArray();
        $problema_estadistica = DB::table('disponible')
        ->join('problemas', 'problemas.id', '=', 'disponible.id_problema')
        ->join('casos__pruebas', 'problemas.id', '=', 'casos__pruebas.id_problema')
        ->select('problemas.nombre','disponible.cantidad_resueltos', 'disponible.cantidad_intentos', 'disponible.tiempo_total', 'cant_retroalimentacion_solicitada', DB::raw('count(casos__pruebas.id) as total_casos'), DB::raw('sum(casos__pruebas.puntos) as puntaje_total'))
        ->where('disponible.id_problema','=', $request->id_problema)
        ->where('disponible.id_curso', '=', $request->id_curso)
        ->groupBy('problemas.nombre','disponible.cantidad_resueltos', 'disponible.cantidad_intentos', 'disponible.tiempo_total', 'cant_retroalimentacion_solicitada')
        ->first();
        if($cantidad_solucionados !=0){
            $problema_estadistica->tiempo_promedio = $problema_estadistica->tiempo_total/$cantidad_solucionados;
        }else{
            $problema_estadistica->tiempo_promedio = 0;
        }
        $problema_estadistica->tiempo_promedio = gmdate('H:i:s', $problema_estadistica->tiempo_promedio);
        return view('informes.problemas.informe', compact('estadistica_estados', 'envios', 'lenguajes_estadistica', 'problema_estadistica', 'cantidad_solucionados'))->with("id_problema", $request->id_problema);
    }

    public function ver_informe_curso(Request $request){
        if(!auth()->user()->cursos()->where('cursos.id', '=', $request->id_curso)->exists()){
            return redirect()->route('informes.problemas.index', ["id"=>$request->id_problema])->with("Error", "No tienes acceso para ver éste informe porque no estás asignado al curso correspondiente.");
        }
        //Consulta estadistica de los resultados de evaluación de los envios en todos los problemas del curso
        $estadistica_estados = EvaluacionSolucion::join('envio_solucion_problemas', 'envio_solucion_problemas.id', '=', 'evaluacion_solucions.id_envio')
        ->join('cursa', 'envio_solucion_problemas.id_cursa', '=', 'cursa.id')
        ->select('resultado')
        ->where('cursa.id_curso', '=', $request->id_curso)
        ->get()->countBy('resultado')->toArray();
        //Consulta estadistica de los lenguajes utilizados en el curso
        $lenguajes_estadistica = DB::table('lenguajes_programaciones')
        ->join('resolver', 'resolver.id_lenguaje', '=', 'lenguajes_programaciones.id')
        ->join('envio_solucion_problemas', 'envio_solucion_problemas.id_resolver', '=', 'resolver.id')
        ->join('cursa', 'envio_solucion_problemas.id_cursa', '=', 'cursa.id')
        ->select('lenguajes_programaciones.nombre')
        ->where('cursa.id_curso', '=', $request->id_curso)
        ->whereNotNull('envio_solucion_problemas.termino')
        ->get()->countBy('nombre')->toArray();
        //Consulta estadistica sobre la cantidad de resueltos y de intentos en todos los problemas del curso, cantidad de solicitud de retroalimentación y el tiempo total de desarrollo de todos los problemas
        $curso_estadistica = DB::table('disponible')
        ->join('cursos', 'disponible.id_curso', '=', 'cursos.id')
        ->select('cursos.id', 'cursos.nombre',DB::raw('sum(disponible.cantidad_resueltos) as sum_cantidad_resueltos'), DB::raw('sum(disponible.cantidad_intentos) as sum_cantidad_intentos'), DB::raw('sum(disponible.tiempo_total) as sum_tiempo_total'), DB::raw('sum(cant_retroalimentacion_solicitada) as sum_cant_ra_solicitada'))
        ->where('disponible.id_curso', '=', $request->id_curso)
        ->groupBy('cursos.id', 'cursos.nombre')
        ->first();
        //Consulta del problemas más resuelto y el problema con más intentos
        $dataset_problemas = DB::table('problemas')
        ->join('disponible', 'disponible.id_problema', '=', 'problemas.id')
        ->where('disponible.id_curso', '=', $request->id_curso)  
        ->select('problemas.nombre', 'problemas.codigo', 'problemas.id','disponible.cantidad_intentos', 'disponible.cantidad_resueltos', 'disponible.cant_retroalimentacion_solicitada', 'disponible.tiempo_total')
        ->distinct();
        $problema_mas_intentado = clone $dataset_problemas;
        $problema_mas_resuelto = clone $dataset_problemas;
        $problema_mas_intentado = $problema_mas_intentado->orderBy('cantidad_intentos', 'DESC')->first();
        $problema_mas_resuelto = $problema_mas_resuelto->orderBy('cantidad_resueltos', 'DESC')->first();
        $dataset_problemas = $dataset_problemas->orderBy('cantidad_resueltos','DESC')->limit(5)->get();
        //Consulta de listado de estudiantes en el curso, con la cantidad de problemas resueltos, cantidad de intento de solucion de problemas y la cantidad de solicitud de retroalimentación.
        $subquery_envios = DB::table('envio_solucion_problemas')
        ->leftJoin('solicitud_ra_llms', 'solicitud_ra_llms.id_envio', '=', 'envio_solucion_problemas.id')
        ->join('resolver', 'resolver.id', '=', 'envio_solucion_problemas.id_resolver')
        ->join('problemas', 'resolver.id_problema', '=', 'problemas.id')
        ->join('cursa', 'cursa.id', '=', 'envio_solucion_problemas.id_cursa')
        ->select('cursa.id_usuario', DB::raw('count(envio_solucion_problemas.id) as cantidad_intentos'), DB::raw('CAST(sum(envio_solucion_problemas.solucionado) AS int) as cantidad_resueltos'), DB::raw('count(solicitud_ra_llms.id) as cantidad_ra'))
        ->orderByDesc('cantidad_intentos')
        ->groupBy('cursa.id_usuario');
        $listado_estudiantes = DB::table('users')
        ->joinSub($subquery_envios, 'informacion_envios', 'informacion_envios.id_usuario', '=', 'users.id')
        ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->select('users.id as id_usuario','users.firstname', 'users.lastname', 'users.rut', 'cantidad_intentos', 'cantidad_resueltos', 'cantidad_ra')
        ->where('roles.name','LIKE', '%estudiante%')
        ->get();
        if($curso_estadistica->sum_cantidad_resueltos !=0){
            $curso_estadistica->tiempo_promedio = $curso_estadistica->sum_tiempo_total/$curso_estadistica->sum_cantidad_resueltos;
        }else{
            $curso_estadistica->tiempo_promedio = 0;
        }
        $curso_estadistica->tiempo_promedio = gmdate('H:i:s', $curso_estadistica->tiempo_promedio);
        //dd($listado_estudiantes, $problema_mas_intentado, $problema_mas_resuelto, $estadistica_estados, $lenguajes_estadistica, $curso_estadistica);
        return view('informes.cursos.informe', compact('dataset_problemas','listado_estudiantes', 'problema_mas_intentado', 'problema_mas_resuelto', 'estadistica_estados', 'lenguajes_estadistica', 'curso_estadistica'));
    }

    public function ver_envios_curso(Request $request){
        $curso = Cursos::find($request->id_curso);
        if(!isset($curso)){
           return redirect()->route('informes.problemas.index', ["id"=>$request->id_problema])->with("Error", "El curso o problema no existe");
        }
        if(!auth()->user()->cursos()->where('cursos.id', '=', $request->id_curso)->exists()){
           return redirect()->route('informes.problemas.index', ["id"=>$request->id_problema])->with("Error", "No tienes acceso para ver éste informe");
        }
        $ultima_evaluacion = DB::table('evaluacion_solucions')
        ->select('resultado', 'id_envio', 'estado')
        ->where('estado', '=', 'Rechazado')
        ->orWhere('estado', '=', 'En Proceso')
        ->orWhere('estado', '=', 'Error')
        ->orderBy('updated_at','DESC')
        ->groupBy('id_envio', 'resultado', 'estado');
        $envios = DB::table("envio_solucion_problemas")
        ->join('resolver', 'resolver.id', '=', 'envio_solucion_problemas.id_resolver')
        ->join('cursa', 'cursa.id', '=', 'envio_solucion_problemas.id_cursa')
        ->join('problemas', 'problemas.id', '=', 'resolver.id_problema')
        ->leftJoin('casos__pruebas', 'casos__pruebas.id_problema', '=', 'problemas.id')
        ->join("users", "users.id", "=", "cursa.id_usuario")
        ->join("lenguajes_programaciones", "lenguajes_programaciones.id", "=", "resolver.id_lenguaje")
        ->leftJoinSub($ultima_evaluacion, 'ultima_evaluacion', function (JoinClause $join){
            $join->on('envio_solucion_problemas.id', '=', 'ultima_evaluacion.id_envio');
        })
        ->select("envio_solucion_problemas.token","cursa.id_curso", "problemas.nombre","problemas.codigo", "resolver.id_problema","users.firstname", "users.lastname", "users.rut", 'envio_solucion_problemas.token', 'envio_solucion_problemas.cant_casos_resuelto','envio_solucion_problemas.puntaje','lenguajes_programaciones.nombre as nombre_lenguaje', 'envio_solucion_problemas.solucionado', 'envio_solucion_problemas.inicio', 'envio_solucion_problemas.termino', 'ultima_evaluacion.resultado', 'ultima_evaluacion.estado', DB::raw('count(casos__pruebas.id) as total_casos'))
        ->where("cursa.id_curso", "=", $request->id_curso)
        ->whereNull('id_certamen')
        ->whereNotNull("termino")
        ->groupBy("envio_solucion_problemas.token","cursa.id_curso", "problemas.nombre","resolver.id_problema", "problemas.codigo","users.firstname", "users.lastname", "users.rut", 'envio_solucion_problemas.token', 'envio_solucion_problemas.cant_casos_resuelto','envio_solucion_problemas.puntaje','lenguajes_programaciones.nombre', 'envio_solucion_problemas.solucionado', 'envio_solucion_problemas.inicio', 'envio_solucion_problemas.termino', 'ultima_evaluacion.resultado', 'ultima_evaluacion.estado')
        ->orderBy("envio_solucion_problemas.created_at", "DESC")
        ->orderBy("users.firstname", "ASC");
        if(isset($request->id_usuario)){
            $envios = $envios->where('users.id', '=', $request->id_usuario);
        }
        $envios = $envios->get();
        return view("informes.cursos.envios", compact("envios", "curso"));
    }

}
