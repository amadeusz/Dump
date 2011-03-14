var dimensions = {};
var albums = {};

Viewer = {
	photos : [],
	opened : 0,
	available_width : undefined,
	available_height : undefined,
	thumbnails_offset : undefined,
	thumbnails_space : undefined,
	thumbnails_width : undefined,
	
	layout : function() {
		var margin = 10
		var window_width = $(window).width();
		var window_height = $(window).height();

		this.available_width = window_width -2 * margin
		this.available_height = window_height -2 * margin
		if($('#left').is(':visible')) this.available_width -= $('#left').outerWidth(true);
		if($('#right').is(':visible')) this.available_width -= $('#right').outerWidth(true);
		if($('#top').is(':visible')) this.available_height -= $('#top').outerHeight(true);
		if($('#bottom').is(':visible')) this.available_height -= $('#bottom').outerHeight(true);

		var x = margin, y = margin;
		if ($('#left').is(':visible')) x += $('#left').outerWidth(true);
		if ($('#top').is(':visible')) y += $('#top').outerHeight(true);
	
		var side_height = window_height - margin;
		if($('#bottom').is(':visible')) side_height -= $('#bottom').outerHeight(true);
		
		$('#pokaz').css('width', this.available_width).css('height', this.available_height).css('left', x).css('top', y);
		$('#top').css('left', x).css('width', this.available_width);
		$('#left').css('height', side_height);
		$('#right').css('height', side_height);
	},
	
	feed : function(photos) {
		this.photos = photos;
		this.opened = 0;
		$('#miniatury').html('');
		
		for(var i = 0; i < this.photos.length; i++)
			$('#miniatury').append('<span class="slide"><img src="thumbnail/'+ this.photos[i][0] +'-100x60' +'" width="'+ this.photos[i][3] +'" height="'+ this.photos[i][4] +'" /></span>');
	
		$('#miniatury span.slide').each( function(i, val) {
			$(this).click( function() {
				Viewer.show(i);
			});
		});
	},
	
	filled : function() {
		return this.photos.length > 0;
	},
	
	calculate_thumbnails : function () {
		var css_offset = $('#miniatury').css("margin-left");
		this.thumbnails_offset = - new Number(css_offset.substr(0, css_offset.length -2));
		
		this.thumbnails_space = $('#miniatury').width() - this.thumbnails_offset;	

		var width = 0;
		$('#miniatury span.slide').each( function() {
			width += $(this).outerWidth(true);
		} );
		
		this.thumbnails_width = width;
	},
	
	slide : function(thumbnail, czas) {
		this.calculate_thumbnails();
		nowa_pozycja = Math.round(this.thumbnails_offset + thumbnail.position().left - this.thumbnails_space/2 + thumbnail.outerWidth(true) /2);
		$('#miniatury').animate( { marginLeft: - nowa_pozycja +'px' }, czas );
	},
	
	show : function(i) {
		if (!this.filled()) return;
	
		if(typeof i != 'undefined') this.opened = i;
		$('#pokaz img:first').css('width', '0px').css('height', '0px').attr('src', '').css('margin-top', '0px');
	
		var r_szerokosc = this.photos[this.opened][1];
		var r_wysokosc = this.photos[this.opened][2];
	
		var szerokosc, wysokosc;
	
		if(r_szerokosc > this.available_width || r_wysokosc > this.available_height) {
			ratio = r_szerokosc / r_wysokosc;
			dostepne_ratio = this.available_width / this.available_height

			if(ratio > dostepne_ratio) {
				szerokosc = this.available_width;
				wysokosc = Math.round(this.available_width / ratio);
				$('#pokaz img:first').css('width', '').css('height', wysokosc);
			} else {
				wysokosc = this.available_height;
				szerokosc = Math.round(this.available_height * ratio);
				$('#pokaz img:first').css('width', szerokosc).css('height', '');	
			}
		}
		else {
			szerokosc = r_szerokosc;
			wysokosc = r_wysokosc;
			$('#pokaz img:first').css('height', '').css('width', '');
		}
	
		gorny_margines = Math.round((this.available_height - wysokosc) / 2);
		$('#pokaz img:first').attr('src', 'photo/'+ this.photos[this.opened][0]).css('margin-top', gorny_margines);
		
		this.slide($('#miniatury span.slide:eq('+ this.opened +')'), 500);
	},
	
	show_next : function() {
		if(this.filled()) {
			if(this.opened < this.photos.length -1) {
				$('#miniatury span.slide:eq('+ (this.opened +1) +')').click();
			}
			else {
				if ($("#top span.highlight").next())
					$("#top span.highlight").next().click();
			}
		}
	}
}

function generate_list(nazwa) {
	var photos = [];
	for (var klucz in albums[nazwa]) {
		var slajd = albums[nazwa][klucz];
		photos.push([slajd, dimensions[slajd][0], dimensions[slajd][1], dimensions[slajd][2], dimensions[slajd][3]]);
	}
	return photos;
}

$(document).ready( function() {
	$(window).resize( function() {
		Viewer.layout();
		Viewer.show();
	});	

	$.get('dimensions', function(data) {
		dimensions = $.parseJSON(data);
		
		$.get('order', function(data) {
			albums = $.parseJSON(data);
			
			$("#top").html("");
			
			names = [];
			for (var tab_title in albums) {
				if(albums[tab_title].length > 0)
					names.push(tab_title);
			}
			names.sort();
			
			for (var tab_title in names)
				$("#top").append('<span>'+ names[tab_title] +'</span>');
			
			$("#top span").click( function() {
				$("#top span").removeClass('highlight');
				$(this).addClass('highlight');
				
				Viewer.feed(generate_list($(this).text()));
				Viewer.layout();
				Viewer.show();
			}).first().click();

			$('#pokaz').click( function() { Viewer.show_next(); });
		});
	});
});

