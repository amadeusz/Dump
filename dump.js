var dimensions = {};
var albums = {};

var Photo = function(id) {
	if (dimensions[id] != undefined) {
		this.id = id;
			
		this.width = dimensions[id][0];
		this.height = dimensions[id][1];
		this.thumbnail_width = dimensions[id][2];
		this.thumbnail_height = dimensions[id][3];
	}
	else throw new Error("e : dimensions hash empty");
}

var Viewer = {
	photos : [],
	opened : undefined,
	highlighted : false,
	buffered : {},
	configuration : { thumbnail_max_width : 100, thumbnail_max_height : 60 },
	available_width : undefined,
	available_height : undefined,
	thumbnails_offset : undefined,
	thumbnails_space : undefined,
	thumbnails_width : undefined,
	
	layout : function() {
		var margin = 10
		var window_width = $(window).width();
		var window_height = $(window).height();

		this.available_width = window_width -2 * margin;
		this.available_height = window_height -2 * margin;
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
		
		$('#previous').css('width', Math.round(this.available_width/10)).css('height', this.available_height).css('left', x).css('top', y);
		$('#next').css('width', Math.round(this.available_width/10)).css('height', this.available_height).css('left', x + Math.round(this.available_width*(9/10))).css('top', y);
	},
	
	feed : function(photos) {
		this.photos = photos;
		this.buffered = {};
		this.opened = 0;
		
		$('#miniatury').html('');
		
		var that = this;
		$.each(this.photos, function(key, photo) {
			if ($.type(photo) == 'object') {
				$('#miniatury').append('<span class="slide"><img src="thumbnail/'+ photo.id +'-'+ that.configuration.thumbnail_max_width +'x'+ that.configuration.thumbnail_max_height +'" width="'+ photo.thumbnail_width +'" height="'+ photo.thumbnail_height +'" /></span>');
			}
			else {
				$('#miniatury').append('<span class="slide">' + $.map(photo, function(subphoto) {
					return '<img src="thumbnail/'+ subphoto.id +'-'+ that.configuration.thumbnail_max_width +'x'+ that.configuration.thumbnail_max_height +'" width="'+ subphoto.thumbnail_width +'" height="'+ subphoto.thumbnail_height +'" />'
				}).join("") + '</span>');
			}
		});

		$('#miniatury span.slide').each(function(i, val) {
			$(this).click( function() {
				Viewer.show(i);
			});
		});
		this.highlight_all();
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
		$('#miniatury').stop().animate( { marginLeft: - nowa_pozycja +'px' }, czas );
	},
	
	show : function(i) {
		if (!this.filled()) return;
	
		var last_opened = this.opened;
		if(typeof i != 'undefined') this.opened = i;
		
		$('#pokaz').stop(true, true).animate( { opacity: 0.0 }, 0).html("");
	
		var to_open = this.photos[this.opened]
		
		if ($.type(to_open) != 'array') {
			to_open = [to_open];
		}

		var real_width_sum = 0, real_height = 0, scaled = false;
		$.each(to_open, function(key, photo) {
			real_width_sum += photo.width;
			if (photo.height > real_height) {
				real_height = photo.height;
			}
		});
			
		var width = real_width_sum, height = real_height;
		var width_attr = '', height_attr = '';

		// Ewentualne dopasowanie
		
		if(real_width_sum > this.available_width || real_height > this.available_height) {
			ratio = real_width_sum / real_height;
			available_ratio = this.available_width / this.available_height;

			if(ratio > available_ratio) {
				width = this.available_width;
				height_attr = height = Math.round(width / ratio);
			} else {
				height = this.available_height;
				width_attr = width = Math.round(height * ratio);
			}
			
			scaled = true;
		}
		
		var that = this;
		$.each(to_open, function(key, photo) {
			var top_margin = 0;
			
			if (real_width_sum > that.available_width) {	
				width_attr = Math.round(width * (photo.width / real_width_sum)) - 10;
			}
			
			if (that.available_height > height) {
				top_margin = (that.available_height - height) /2;
			}
			
			$('#pokaz').append('<img id="photo_'+ photo.id +'" />').find('img#photo_'+ photo.id).css('width', width_attr).css('height', height_attr).css('padding-top', top_margin +'px').attr('src', 'photo/'+ photo.id).attr('alt', '').parent().animate( {opacity: 1.0 } );
		});
	
		var opened = this.opened;
		this.slide($('#miniatury span.slide:eq('+ this.opened +')'));
		this.highlight(last_opened);
	},
	
	show_next : function() {
		if(this.filled()) {
			if(this.opened < this.photos.length -1) {
				$('#miniatury span.slide:eq('+ (this.opened +1) +')').click();
			}
			else {
				if ($("#top a.highlight").next())
					$("#top a.highlight").next().click();
			}
		}
		Viewer.buffer();
	},
	
	show_previous : function() {
		if(this.filled()) {
			if(this.opened > 0) {
				$('#miniatury span.slide:eq('+ (this.opened -1) +')').click();
			}
			else {
				if ($("#top a.highlight").prev())
					$("#top a.highlight").prev().click();
			}
		}
		Viewer.buffer();
	},
	
	buffer : function() {
		var to_buffer = [];
		for (var i = 1; i < 3; i++) {
			if (this.opened + i < this.photos.length) {
				if ($.type(this.photos[this.opened + i]) == 'array') {
					to_buffer = $.merge(to_buffer, this.photos[this.opened + i]);
				}
				else {
					to_buffer.push(this.photos[this.opened + i]);
				}
			}
		}
	
		var that = this;
		$.each(to_buffer, function(key, photo) {
			if (that.buffered[photo.id] === undefined) {
				that.buffered[photo.id] = new Image(photo.width, photo.height);
				that.buffered[photo.id].src = 'photo/'+ photo.id;
			}
		});
	},
	
	highlight : function(last) {
		if (last !== this.opened) {
			if (!this.highlighted) {
				if (last != undefined)
					$('#miniatury span.slide:eq('+ last +')').animate( { opacity: 0.05 } );
				$('#miniatury span.slide:eq('+ this.opened +')').animate( { opacity: 0.2 } );
			}
		}
	},
	
	highlight_all : function(should_i) {
		if (should_i === undefined)
			should_i = this.highlighted;
			
		if (should_i) {
			this.highlighted = true;
			$("#miniatury span").animate( { opacity: 1.0 } );
		}
		else {
			this.highlighted = false;
			$("#miniatury span").animate( { opacity: 0.05 } );
			$('#miniatury span.slide:eq('+ this.opened +')').animate( {opacity: 0.2} );
		}
	}
}

function generate_list(name) {
	var photos = [];
	
	photos = $.map(albums[name], function(element) {
		if($.type(element) == 'string') {
			return new Photo(element);
		}
		else {
			return [$.map(element, function (subelement) {
				return new Photo(subelement);
			})];
		}
	});
	
	return photos;
}

$(document).ready( function() {
	$(window).resize( function() {
		Viewer.layout();
		Viewer.show();
	});
	
	$("#miniatury").hover(function() {
		Viewer.highlight_all(true);
	},
	function() {
		Viewer.highlight_all(false);
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
				$("#top").append('<a>'+ names[tab_title] +'</a>');
			
			$("#top a").click( function() {
				$("#top a").removeClass('highlight');
				$(this).addClass('highlight');
				
				Viewer.feed(generate_list($(this).text()));
				Viewer.layout();
				Viewer.show();
			}).first().click();

			$('#pokaz').click( function() { Viewer.show_next(); });
			$('#next').click( function() { Viewer.show_next(); });
			$('#previous').click( function() { Viewer.show_previous(); });			
			
			Viewer.buffer();
		});
	});
});



