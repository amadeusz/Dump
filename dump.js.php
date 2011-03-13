<?php header('Content-type: text/javascript'); ?>

var zdjecia = [];
var cache = [];
var dimensions = {};
var albums = {};

var margines = 5
var szerokosc_okna, wysokosc_okna, dostepna_szerokosc, dostepna_wysokosc
var gora, prawo, dol, lewo
var gora_widoczna, prawo_widoczne, dol_widoczny, lewo_widoczne
var r_szerokosc, r_wysokosc, szerokosc, wysokosc
var otwarte = 0

function odswiez_panele() {
	szerokosc_okna = $(window).width(); wysokosc_okna = $(window).height();
	gora_widoczna = $('#gora').is(':visible'); prawo_widoczne = $('#prawo').is(':visible'); dol_widoczny = $('#dol').is(':visible'); lewo_widoczne = $('#lewo').is(':visible');
	gora = $('#gora').outerHeight(); prawo = $('#prawo').outerWidth(); dol = $('#dol').outerHeight(); lewo = $('#lewo').outerWidth();

	dostepna_szerokosc = szerokosc_okna -2*margines
		if(lewo_widoczne) dostepna_szerokosc -= lewo
		if(prawo_widoczne) dostepna_szerokosc -= prawo;
	dostepna_wysokosc = wysokosc_okna - 2*margines
		if(gora_widoczna) dostepna_wysokosc -= gora
		if(dol_widoczny) dostepna_wysokosc -= dol;
	
	$('#pokaz').css('width', dostepna_szerokosc).css('height', dostepna_wysokosc);
	if(lewo_widoczne) $('#pokaz').css('left', $('#lewo').width() + margines)
		else $('#pokaz').css('left', margines)
	if(gora_widoczna) $('#pokaz').css('top', $('#gora').height() + margines);	
		else $('#pokaz').css('top', margines)	
	
	$('#gora').css('width', dostepna_szerokosc);
	if(lewo_widoczne) $('#gora').css('left', lewo + margines);
		else $('#gora').css('left', margines);
	
	if(dol_widoczny) $('#lewo').css('height', wysokosc_okna - $('#dol').height() - margines);
		else $('#lewo').css('height', wysokosc_okna - margines);
		
	if(dol_widoczny) $('#prawo').css('height', wysokosc_okna - $('#dol').height() - margines);
		else $('#prawo').css('height', wysokosc_okna - margines);
}

function odswiez(i) {
	if (zdjecia.length == 0) return;
	
	if(typeof i != 'undefined') otwarte = i;
	$('#pokaz img:first').css('width', '').css('height', '').attr('src', '').css('margin-top', '');
	
	r_szerokosc = zdjecia[otwarte][1];
	r_wysokosc = zdjecia[otwarte][2];
	
	if(r_szerokosc > dostepna_szerokosc || r_wysokosc > dostepna_wysokosc) {
		ratio = r_szerokosc / r_wysokosc;
		dostepne_ratio = dostepna_szerokosc / dostepna_wysokosc

		if(ratio > dostepne_ratio) {
			szerokosc = dostepna_szerokosc;
			wysokosc = Math.round(dostepna_szerokosc / ratio);
			$('#pokaz img:first').css('width', '').css('height', wysokosc);
		} else {
			wysokosc = dostepna_wysokosc;
			szerokosc = Math.round(dostepna_wysokosc * ratio);
			$('#pokaz img:first').css('width', szerokosc).css('height', '');	
		}
	}
	else {
		szerokosc = r_szerokosc;
		wysokosc = r_wysokosc;
		$('#pokaz img:first').css('height', '').css('width', '');
	}
	
	gorny_margines = Math.round((dostepna_wysokosc - wysokosc) / 2);
	$('#pokaz img:first').attr('src', 'photo/'+ zdjecia[otwarte][0]).css('margin-top', gorny_margines);
	przesun($('#miniatury img:eq('+ otwarte +')'), 500);
}

var przesuniecie_miniatur, szerokosc_obszaru_miniatur, szerokosc_miniatur

function oblicz_miniatury() {
	przesuniecie_miniatur = $('#miniatury').css("margin-left");
	przesuniecie_miniatur = new Number(przesuniecie_miniatur.substr(0, przesuniecie_miniatur.length-2));
	przesuniecie_miniatur = - przesuniecie_miniatur
	
	szerokosc_obszaru_miniatur = $('#miniatury').width() - przesuniecie_miniatur;	

	var szerokosc = 0;
	$('#miniatury img').each( function() {
		szerokosc += $(this).outerWidth(true);
	} );
	szerokosc_miniatur = szerokosc
}

function przesun(obj, czas) {
	oblicz_miniatury();
	
	var pozycja_miniatury = obj.position().left;
	var szerokosc_miniatury = obj.outerWidth(true);				
	
	nowa_pozycja = Math.round(przesuniecie_miniatur + pozycja_miniatury - szerokosc_obszaru_miniatur/2 + szerokosc_miniatury /2);
	
	$('#miniatury').animate( {
		marginLeft: -nowa_pozycja +'px'
	}, czas );
}

function napelnij() {
	$('#miniatury').html('');
	
	var i; for(i = 0; i < zdjecia.length; i++) {
		$('#miniatury').append('<img id="zdjecie_'+ i +'" src="thumbnail/'+ zdjecia[i][0] +'-160x100' +'" width="'+ zdjecia[i][3] +'" height="'+ zdjecia[i][4] +'" />');
	}
	
	$('#miniatury img').each( function(i, val) {
		$(this).click( function() {
			odswiez(i);
		} );
	} );
}

function generuj_liste(nazwa) {
	zdjecia = [];
	for (var klucz in albums[nazwa]) {
		var slajd = albums[nazwa][klucz];
		zdjecia.push([slajd, dimensions[slajd][0], dimensions[slajd][1], dimensions[slajd][2], dimensions[slajd][3]]);
	}
}

$(document).ready( function() {

	// $("#gora").hide();
	$("#lewo").hide();	
	$("#prawo").hide();

//	$('#przelacznik_gory').click( function() {
//		$('#gora').toggle();
//		odswiez_panele();
//		odswiez();
//	} );

//	$('#przelacznik_prawego').click( function() {
//		$('#prawo').toggle();
//		odswiez_panele();
//		odswiez();
//	} );

//	$('#przelacznik_dolu').click( function() {
//		$('#dol').toggle();
//		odswiez_panele();
//		odswiez();
//	} );

//	$('#przelacznik_lewego').click( function() {
//		$('#lewo').toggle();
//		odswiez_panele();
//		odswiez();
//	} );

	$.get('dimensions', function(data) {
		dimensions = $.parseJSON(data);
		
		$.get('order', function(data) {
			albums = $.parseJSON(data);
			$("#gora").html("");
			for (var tab_title in albums) {
				$("#gora").append('<a>'+ tab_title +'</a>');
			}
	
			$("#gora a").click(function() {
				generuj_liste($(this).text());
				feed();
			});
			
		});
	});

	function feed() {
		napelnij();
	
		$('#prawo').click( function() {	
			oblicz_miniatury();	
			if(przesuniecie_miniatur + szerokosc_obszaru_miniatur*0.66 < szerokosc_miniatur - szerokosc_obszaru_miniatur/2 - $('#miniatury img:last').outerWidth(true)/2) {
				$('#miniatury').animate( {
					marginLeft: -(przesuniecie_miniatur + szerokosc_obszaru_miniatur*0.66) +'px'
				}, 500 );
			}
			else przesun($('#miniatury img:last'), 500);
		} );

		$('#lewo').click( function() {	
			oblicz_miniatury();	
			if(przesuniecie_miniatur > szerokosc_obszaru_miniatur/2 + $('#miniatury img:first').outerWidth(true)/2) {
				$('#miniatury').animate( {
					marginLeft: -(przesuniecie_miniatur - szerokosc_obszaru_miniatur*0.66) +'px'
				}, 500 );
			}
			else przesun($('#miniatury img:first'), 500);
		} );

		$('#pokaz').click( function() {
			if(otwarte < zdjecia.length) {
				$('#miniatury img:eq('+ (otwarte+1) +')').click();
			}
		} );
	
		$('#gora').click( function() {
			if(otwarte > 0) {
				$('#miniatury img:eq('+ (otwarte-1) +')').click();
			}
		} );
		
		$(window).resize( function() {
			odswiez_panele();
			odswiez();
		} );
		
		$(window).resize();
	}

} );

