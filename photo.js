Array.prototype.unique =
	function() {
		var a = [];
		var l = this.length;
		for(var i = 0; i < l; i++) {
			for(var j =  i +1; j < l; j++)
				if (this[i] === this[j]) j = ++i;
			a.push(this[i]);
		}
		return a;
	};

var albums = {}

Album = {
	load : function() {
		$.get('order', function(data) {
			albums = $.parseJSON(data);

			if (!albums) {
				albums = {};
			}
			
			if (false) {
				albums = {
					"Zdjęcia brzuszkowe": [['4204f6ec1048cbd77f9c5ba3d56af8bc1789b72d', 'a6544a19d8003ef8442c580aaa05595a4f270518'], 'cce7b98209eb0c12226ad332d33a74341c811947'],
					"Pijackie imprezy": ['fcffd9c23b794d45443d819881901b2576267ec2', '4204f6ec1048cbd77f9c5ba3d56af8bc1789b72d']
				};
			}
	
			$("#buttons select").html("");
			
			var tab_title; 
			for (tab_title in albums) {
				$("#buttons select").append('<option>'+ tab_title +'</option>');
			}
			Album.activate_buttons();
		});
	},
	save : function() {
		var arr = [];
		$("ul#container li").each( function() {
			if($(this).children().length > 1) {
				var sub_arr = []
				$(this).children().each( function() {
					sub_arr.push($(this).attr('data-src'));
				} );
				arr.push(sub_arr);
			}
			else arr.push($(this).find('img').attr('data-src'));
		});
		albums[Album.current()] = arr;
		var str = JSON.stringify(albums);
		$.post("order", { order: str });
	},
	refresh : function(album) {
		$("ul#container").html('');

		for (var photo in albums[album])
			if (albums[album].hasOwnProperty(photo)) {
				$("ul#container").append('<li></li>')
				if ((albums[album][photo]).length < 10)  {
					for (var half_photo in albums[album][photo])
						if (albums[album][photo].hasOwnProperty(half_photo)) {
							$("ul#container li:last-child").append('<img data-src="'+ albums[album][photo][half_photo] +'" src="thumbnail/'+ albums[album][photo][half_photo] +'/100x100" />');
							$("ul#container li:last-child").children().addClass("half");
						}
				}
				else {
					$("ul#container li:last-child").append('<img data-src="'+ albums[album][photo] +'" src="thumbnail/'+ albums[album][photo] +'/100x100" />');
					$("ul#container li:last-child").children().removeClass("half");
				}
			}
		this.align_thumbnails();
	},
	current : function() {
		return $("#buttons select option:selected").text();
	},
	generate : function() {
		$("ul#container img").each( function() {
			alert($(this).attr('data-src'));
			$.get('thumbnail/'+ $(this).attr('data-src') +'/160x100');
		});
	},
	activate_buttons : function() {
		Album.refresh(Album.current());
	
		$("#buttons select").change( function() {
			Album.refresh(Album.current());
		});
	
		$("#buttons #new").click( function() {
			var new_name = prompt("Podaj nazwę nowego albumu");
			if (new_name == null) return;
		
			var lista = [];
			$("#buttons select option").each( function() {
				lista.push($(this).text());
			});
		
			if($.inArray(new_name, lista) == -1) {
				$("#buttons select").append('<option selected="selected">'+ new_name +'</option>');
				albums[$("#buttons select option:selected").text()] = [];
				$("#buttons select").change();
				Album.save();
			}
			else alert("Album już istnieje");
		});
	
		$("#buttons #rename").click( function() {
			var new_name = prompt("Podaj nową nazwę", Album.current());
			if (new_name == null) return;
		
			var lista = [];
			$("#buttons select option").each( function() {
				lista.push($(this).text());
			});		

			if($.inArray(new_name, lista) === -1) {
				var old_name = Album.current();
				albums[new_name] = albums[old_name];
				delete albums[old_name];
				$("#buttons select option:selected").text(new_name);
				Album.save();
			}
			else alert("Album już istnieje");
		});
	
		$("#buttons #delete").click( function() {
			var old_name = Album.current();
			if(confirm("Czy na pewno chcesz usunąć cały album?")) {
				delete albums[old_name];
				$("#buttons select option").each( function() {
					if ($(this).text() === old_name)
						$(this).remove();
				});
				$("#buttons select").change();
				Album.save();
			}
		});
	
	},
	align_thumbnails : function() {
		$('#container img').each( function() {
			$(this).css('margin-top', ($(this).parent().height() - $(this).height())/2 +'px');
		} );
	}
}

$( function() {
	var press = false;
	var list = [];

	$('#container li').live('dblclick', function() {
		if(confirm("Czy na pewno chcesz usunąć?")) {
			$(this).remove();
			Album.save();
		}
	});

	$('#container').sortable({
		placeholder: 'placeholder',
		update: function(event, ui) {
			Album.save();
		}
	});
	$('#container').disableSelection();
	
	$('#container').sortable( 'option', 'connectWith', '#upcoming' );

	$('#container li').live('click', function() {
		if (press) {
			if (list.length == 0)
				$(this).addClass('master');
			else $(this).addClass('joining');
			list.push(this);
		}
	});

	$(window).keydown( function(event) {
		if (event.keyCode == 17 || event.keyCode == 16) press = true;
	});
	
	$(window).keyup( function(event) {
		if (event.keyCode == 17 || event.keyCode == 16) {
			press = false;
			list = list.unique();
			if (list.length > 1) {
				first = $(list[0]);
				for(var i = 1; i < list.length; i++) {
					var element = $(list[i]);
					first.html(first.html() +' '+ element.html());
					element.remove();
				}
			}
			else if (list.length == 1) {
				$(list[0]).children().each( function(i) {
					if(i != 0) {
						$(list[0]).after('<li><img data-src="'+ $(this).attr('data-src') +'" src="'+ $(this).attr('src') +'" /></li>');
						$(this).remove();
					}
				});
				$(list[0]).find('img').removeClass('half');
			}
			if (list.length > 0) {
				$(list[0]).removeClass('master');
				$(list[0]).removeClass('joining');
			}
			list = [];
			
			Album.save();
			Album.refresh(Album.current());
		}
	});
	
	$('#dropzone').filedrop({
			url: 'upload.php',              // upload handler, handles each file separately
			paramname: 'kot',          // POST parameter name used on serverside to reference file
//			data: { 
//				album: Album.current()           // send POST variables
//				param2: function() {
//					return calculated_data; // calculate data at time of upload
//				},
//			},
	
//		error: function(err, file) {
//			switch(err) {
//				case 'BrowserNotSupported':
//					alert('browser does not support html5 drag and drop')
//					break;
//				case 'TooManyFiles':
//					alert('TooManyFiles');
//					break;
//				case 'FileTooLarge':
//					alert(file.name);
//					// use file.name to reference the filename of the culprit file
//					break;
//				default:
//					alert(e)
//					break;
//			}
//		},

		maxfiles: 25,
		maxfilesize: 8,    // max file size in MBs
		dragOver: function() {
			$("#dropzone").stop(true, true).animate({ backgroundColor: "#90ee90" }, 500);
		},
	
		dragLeave: function() {
			$("#dropzone").stop(true, true).animate({ backgroundColor: "#68bfef" }, 500);
		},

//		docOver: function() {},
//		docLeave: function() {},
	
//		drop: function() {
//			
//		},
	
		uploadStarted: function(i, file, len){
			//alert('upload started');
		},
	
		uploadFinished: function(i, file, response, time) {
			if(response.success) {
				albums[Album.current()].push(response.hash);
				$.get('thumbnail/'+ response.hash +'/160x100');
				Album.refresh(Album.current());
				Album.save();
			}
		},
	
//		progressUpdated: function(i, file, progress) {
//			
//		},
//	
//		speedUpdated: function(i, file, speed) {

//		},
//	
//		rename: function(name) {},
	
		beforeEach: function(file) {
			//alert('before each');
			// return false to cancel upload
		},
	
//		afterAll: function() {}
	
	});
	
	Album.load();
});
