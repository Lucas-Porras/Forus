<?php
	require_once("partials/session_start.php");
	if (isset($_GET['r'])){
		$rnum=$_GET['r'];
	} else {
		$rnum=0;
	}


?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="shortcut icon" href="cutlery.png">
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
  		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
		<meta charset="UTF-8">
    	<title>Modificando Receta - Recetario</title>



		<script src="https://cdn.ckeditor.com/ckeditor5/31.0.0/classic/ckeditor.js"></script>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
		<script>
			$(function() {
				$('#save').click(function() {
						var mysave = $('.ck-content').html();
						//console.log(mysave);
						
						$('#text-area').val(mysave);
				});
			});
		</script>
		<?php require_once 'partials/starfunc.php'; ?>
		<style>
			.cont1{
				max-width:70vw;
				margin:30px;
			}
		</style>
	</head>
	<body>
	<?php include 'partials/header.php'?>
		<div class="container border  rounded-3 mt-5">
			<h5 class="display-5 text-center">Sube tu receta!</h5>
			<div class="cont1">
				<form onsubmit="return saverecipe(event)" id="fullform">
					<input id='qt' name='qt' type='hidden' value='mr'>
					<label for="name" class="form-label">Titulo</label>
					<input id="name" name="name" type='text'>
					<br>
					<label for="img" class="form-label">Imagen</label>
					<input id="img" name="img" type='file' onchange="displayimg(this)">
					<p>(Imagen actual:)</p>
					<img src="" id="cimg">
					<br>
					<label for="code" class="form-label">Video de youtube</label>
					<input id="code" name="code" type='text'>
					<div name="texto" id="editor">

					</div>

					<textarea name="recipe" id="text-area" style="display:none;">
					</textarea>
					<?php if ($rnum){ ?>
						<input id='v' name='v' type='hidden' value='<?php echo $rnum; ?>'>
					<?php }  ?>
					<input id="save" name="b" type='submit'>
				</form>
			</div>
		</div>
		<script>
			editor=ClassicEditor.create(document.querySelector('#editor'))
			.catch(error =>{
				//console.log('Error');
			});
			<?php if ($rnum){ ?>
			callAPI('rd','<?php echo $rnum; ?>',function(result){
				if (result[0]['user_id']!=<?php echo $_SESSION['id']; ?>){
					alert('Esta no es tu receta!');
					window.location.href="misrecetas.php";
					throw new Error("User IDs Dont match");
				}
				document.getElementById('name').value=result[0]['name'];
				document.getElementById('cimg').src="images/recipe/"+result[0]['img_path']
				document.getElementById('code').value=result[0]['code'];
				//console.log(result);
				editor.then(editorobj =>{editorobj.setData(result[0]['recipe'])})
				
			});
			<?php }  ?>

			function saverecipe(event){
					event.preventDefault();
					var formData =new FormData(document.getElementById("fullform"));
					
					
					

					if (formData.get('code').indexOf('?v=')>0){
						formData.set('code', formData.get('code').split('=')[1].split('/')[0].split('?')[0].split('&')[0]);
					}
					if (formData.get('code').indexOf('.be/')>0){
						formData.set('code',formData.get('code').split('.be/')[1].split('/')[0].split('?')[0].split('&')[0]);
					}
					
					$.ajax({
						url: window.location.pathname.split('/').slice(0,-1).join('/')+"/api/api.php",
						dataType:"json",
						method:"post",
						data: formData,
						processData: false,
    					contentType: false,
						success: function(result){
							if(result){
								window.location.href="misrecetas.php"
							} else {
								alert('no se pudo subir');
							}
						}
					});
					return false;
					
   			 }

			function displayimg(input){
				if (input.files && input.files[0]) {
					var reader = new FileReader();

					reader.onload = function (image) {
						document.getElementById("cimg").src=image.target.result;
					};

					reader.readAsDataURL(input.files[0]);
        		}
			} 
		</script>
	</body>
</html>