
$(document).ready(function() {

	let saveCity = $("input[name*='city']").val();
	let savePostCode = $("input[name*='postcode']").val();

	// Crea un selector de ciudades y lo actualiza dependiendo del departameto seleccionado
	if ($("select[name*='id_country']").length) {
		
		if (validaColombia()){
			
			var sel = $('<select id="select_city" class="form-control ocultar" name="select_city">').insertAfter("input[name*='city']");
			
			getDaneCities();
		}
	}

	// Actualiza las ciudades por departamento seleccionado
	$("select[name*='id_country']").change(function() {
		
		if (validaColombia()) getDaneCities();
	})


	// Actualiza los campos ocultos
	$('#select_city').change(function() {
		
		$("input[name*='city']").val($('#select_city option:selected').text());
		$("input[name*='postcode']").val($('#select_city').val());
	})
	

	function validaColombia() {

		var pais = $("select[name*='id_country'] option:selected").text();


		// Rest of the world
		if (pais != "Colombia") {

			$('.form-group').removeClass("carriertcchide");
			$("input[name*='city']").removeClass("carriertcchide");

			return false;
		}

		// Only from colombia
		$("input[name*='city']").addClass("carriertcchide");
		$("input[name*='postcode']").parent().parent().addClass("carriertcchide");


		return true;
	}


	function getDaneCities() {
		$.getJSON('/modules/carriertcc/views/js/dane.json', function(data) {
			
			loadCities(data);

			$("select[name*='id_state']").change(function() {
				loadCities(data);
			})

		});
	}

	function loadCities(data){

		if (validaColombia()){
			
			selectState = $("select[name*='id_state'] option:selected").text().toLocaleLowerCase();
			
			$('#select_city').html('');
			$("input[name*='city']").val('');
			$("input[name*='postcode']").val('');
			
			// Envita preseleccionar
			$('#select_city').append($("<option>").attr('value','').text(''));
			$('#select_city').val('0').trigger('change');

			
			if(data[selectState]){
				
				$(data[selectState]).each(function() {

					$('#select_city').append($("<option>").attr('value',this.code).text(this.city));
						
				})

				if(savePostCode){
					$('#select_city').val(savePostCode).trigger('change');
				}
			}

			}
	}


})

