<?php 

//Todas las variables se pueden mandar por post o get. get tiene prioridad

//'v' es el valor que se ingresa como query (id o limit o numeros separados por coma o lo que sea). 
///////'qt' significa query type. puede llevar los valores:

//////valores publicos (no requieren un usuario logueado). esta seria el api abierto, que se puede acceder por cualquier usuario o pagina (principalmente la nuestra)
//rd: devuelve los datos de la/s videos indicados en $v. $v recibe un número de id de video o una lista de numeros separados por coma 
//sr: devuelve un array de todos los ids de recetas en el orden pedidio. $v recibe dos letras juntas: a,c,f,v,p para sortear por orden alfabetico, cronologico, por favoritos, vistas o popularidad; y 'a' o 'd' siendo ascendiente o descendiente. 
//cf: devuelve la cantidad de favoritos y la cantidad de favoritos en la ultima semana (popularidad), en un array. Recibe el id de receta en $v
//////valores privados (requieren un usuario logueado con el usuario correcto)
//dr: recibe un id de receta en $v. La cambia de estado (si esta borrada es recuperada y si no la borra). Devuelve True si quedo sin borra y False si quedó borrada
//mr: NO IMPLEMENTADO recibe El id de la receta en $v (0 si es nueva). Usa variables : name,recipe,code,img. Hay que usarlo por post debido al limite del get. Devuelve True si se modificó , False si falló, y el numero de receta si es nuevo
//yr: funciona igual que sr, pero busca las recetas del usuario ordenadas. Devuelve una lista con als recetas sin borrar y otra con borradas
//ys: funciona igual que sr, pero busca las recetas guardadas del usuario ordenadas. Tambien permite usar el valor 'm' como primera letra para ver mas recientes
//sf: cambia el estado de favorito de una receta. Devuelve True si queda en favoritos y False si queda no en favorito.


//ejemplos
//http://localhost/Tarea/proyecto/Forus/api/api.php?qt=rd&v=2,5,8,3
/////////devuelve una lista con arrays asociativos para las recetas 2, 3 y 5. La ocho no la devuelve porque esta borrada. Para acceder a la 8 se deberá usar el api privada desde la cuenta correspondiente
//http://localhost/Tarea/proyecto/Forus/api/api.php?qt=cf&v=3
/////////devuelve un array ["2","1"] con el primer numero siendo el total de favoritos de la receta 3 y el segundo siendo solo los de la ultima semana. Este segundo se puede usar para elejir los mas populares. 
//http://localhost/Tarea/proyecto/Forus/api/api.php?qt=sr&v=fd
/////////devuelve un array con los ids de todos las recetas, en orden de cantidad de favoritos decendiente

function privQSt(){
    require_once '..\partials\session_start.php'; 
    if (isset($_SESSION['id'])){
        return $_SESSION['id'];
    } else {
        
        exit('{"error":"This call requires being logged in","$_SESSION:"'.print_r($_SESSION).'}');
    }
}

function orderAndPush($query,$link){
    $json=[];
    $result=qq($link, $query);
    while ($row=mysqli_fetch_assoc($result)){
        array_push($json,$row['ID']);
    }
    return $json;
};

function sortorder($sorttype, $order, $where){
	$query="SELECT recipes.*, COUNT(favorites.Recipes_ID) as favcount FROM recipes LEFT JOIN favorites ON favorites.Recipes_ID = recipes.ID ".$where." GROUP BY recipes.ID ORDER BY ";
	switch ($sorttype){
		case 'a': $query.="Name"; break;
		case 'c': $query.="Created_At"; break;
		case 'f': $query.="favcount"; break; 
		case 'v': $query.="Views"; break; 
		case 'p': $query="SELECT recipes.*, COUNT(favorites.Recipes_ID) as popularity FROM recipes LEFT JOIN favorites ON favorites.Recipes_ID = recipes.ID AND favorites.Created_At + INTERVAL 7 DAY > NOW() ".$where." GROUP BY recipes.ID ORDER BY popularity"; break;
        }
	switch ($order){
        case 'a': $query.=" ASC"; break;
        case 'd': $query.=" DESC"; break;
    }
	//echo $query;
	return $query;
	
}


if (isset($_GET['qt'])) {
    $qt=$_GET['qt'];
} else if  (isset($_POST['qt'])){
    $qt=$_POST['qt'];
} else {
    exit('{"error":"no query type (qt) on get or post"}');
}

if (isset($_GET['v'])) {
    $value=$_GET['v'];

} else if  (isset($_POST['v'])){
    $value=$_POST['v'];

} else if ($qt=='rd') {
    $value='0,25';
} else if ($qt=='sr') {
    $value='aa';
} else {
    exit('{"error":"no query value"}');
}


require_once '..\database\database.php';
$json=[];

switch($qt){
    case 'rd':
        if (is_numeric($value)){
            $query="SELECT * FROM recipes WHERE ID =".$value." AND Deleted_At IS NULL";
        }
        else {
            
            $idarr=explode (",", $value);
            $query="SELECT * FROM recipes WHERE ( 0 ";
            foreach ($idarr as $subid){
                $query.="OR ID=".$subid." ";
            }
            $query.=" ) AND Deleted_At IS NULL";
        }
        $result=qq($link, $query);
        while ($row=mysqli_fetch_assoc($result)){
            array_push($json, [
                'id'=>$row['ID'],
                'user_id'=>$row['User_ID'],
                'name'=>$row['Name'],
                'recipe'=>$row['Recipe'],
                'views'=>$row['Views'],
                'img_path'=>$row['img_path'],
                'created_at'=>$row['Created_At'],
                'code'=>$row['Code'],
            ]);
        }
        break;


    case 'cf':
        $query="SELECT COUNT('User_ID') as tf FROM favorites WHERE Recipes_ID=".$value;
        $result1=mysqli_fetch_assoc(qq($link, $query))['tf'];
        $query="SELECT COUNT('User_ID') as rf FROM favorites WHERE Recipes_ID=".$value." AND Created_At + INTERVAL 7 DAY > NOW()";
        $result2=mysqli_fetch_assoc(qq($link, $query))['rf'];
        array_push($json,$result1,$result2);        
        break;

    case 'sr':
        $orderarr = str_split($value);
        $query=sortorder($orderarr[0],$orderarr[1],"WHERE Deleted_At IS NULL");
        $json=orderAndPush($query,$link);
        break;
    

    case 'dr':
        $id=privQSt();
        $isdel= mysqli_fetch_assoc(qq($link, "SELECT Deleted_At FROM recipes WHERE ID = ".$value))['Deleted_At'];
        $query="UPDATE recipes SET Deleted_At = ".($isdel ? "NULL" : "NOW()")." WHERE ID = ".$value;
        qq($link, $query);
        array_push($json, $isdel ? true : false);
        break;


    case 'mr':
		$id=privQSt();
		if (isset($_POST['name']) && isset($_POST['recipe']) ){
			if (isset($_FILE['img'])){
				
			}
			
			
			
		} else {
			array_push($json, false);
		}
		break;
	
    case 'yr':
		$id=privQSt();
        $orderarr = str_split($value);
        $query=sortorder($orderarr[0],$orderarr[1],"WHERE recipes.User_ID = ".$id." AND Deleted_At IS NULL");
        array_push($json,orderAndPush($query,$link));
		$query=sortorder($orderarr[0],$orderarr[1],"WHERE recipes.User_ID = ".$id." AND NOT Deleted_At IS NULL");
        array_push($json,orderAndPush($query,$link));
		break;


    case 'ys':
		$id=privQSt();
        $orderarr = str_split($value);
        $query=sortorder($orderarr[0],$orderarr[1],"WHERE recipes.ID = favorites.Recipes_ID AND favorites.User_ID = ".$id." AND Deleted_At IS NULL");
        $json=orderAndPush($query,$link);
		break;
		
    case 'sf':
		$id=privQSt();
        $isfav= mysqli_num_rows(qq($link, "SELECT Created_At FROM favorites WHERE Recipes_ID = ".$value." AND USER_ID=".$id));
        if ($isfav){
			$query="DELETE FROM favorites WHERE Recipes_ID = ".$value." AND USER_ID=".$id;
		} else {
			$query="INSERT INTO favorites VALUES(" . $id . ", " . $value . ", NOW() )";
		}
        qq($link, $query);
        array_push($json, $isfav ? false : true);
        break;

        
}
echo json_encode($json);
?>

