
$(document).ready(function() {


	/**
	 *
	 * Check Colombia
	 *
	 */

	if ($('#id_country').length) {
		var sel = $('<select id="id_newcity" class="form-control ocultar" name="id_newcity">').insertAfter('#city');
		// setTimeout(function(){$('#uniform-id_newcity').hide()},2000);
		
		if (validaColombia()) getDaneCities();
	}

	$('#id_country').change(function() {
		if (validaColombia()) getDaneCities();
	})


	// New select when is from Colombia
	$('#id_newcity').change(function() {
		//getDaneCities();
		$('#city').val($('#id_newcity option:selected').text());
		$('#postcode').val($('#id_newcity').val());


	})
	


	/**
	 *
	 * Valída Colombia
	 *
	 */

	function validaColombia() {

		var pais = $('#id_country option:selected').text();

		if (pais != "Colombia") {

			// Rest of the world
			$('.postcode').removeClass("ocultar");
			$('#city').removeClass("ocultar");
			$('#uniform-id_newcity').addClass("ocultar");
			setTimeout(function() {
				$('.postcode').removeClass("ocultar")
				$('#uniform-id_newcity').addClass("ocultar");
			}, 5000);



			return false;
		}

		// only from colombia
		$('.postcode').addClass("ocultar");
		$('#city').addClass("ocultar");
		$('#uniform-id_newcity').removeClass("ocultar");
		setTimeout(function() {
			$('.postcode').addClass("ocultar")
			$('#uniform-id_newcity').removeClass("ocultar");
		}, 5000);



		return true;
	}

	/**
	 *
	 * Get json cities
	 *
	 */

	function getDaneCities() {
		$.getJSON('/modules/carriertcc/views/js/dane.json', function(data) {
			
			loadCities();

			$('#id_state').change(function() {
				loadCities();
			})

			function loadCities(){

				if (validaColombia()){
					
						selectState = $('#id_state option:selected').text().toLocaleLowerCase();
						
						//Limpio campos
						$('#id_newcity').html('');
						$('#city').val('');
						$('#postcode').val('');
						
						//Agrego un intem en blanco en el select
						$('#id_newcity').append($("<option>").attr('value','').text(''));
						$('#id_newcity').val('0').trigger('change');
	
						
						if(data[selectState]){
							
							$(data[selectState]).each(function() {
	
								$('#id_newcity').append($("<option>").attr('value',this.code).text(this.city));
									
							})
						}
	
					}
			}


		});
	}




})

