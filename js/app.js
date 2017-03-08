(function (window) {
	'use strict';

	var App = {
		state: {
			lists: [],
			info: false,
			allDone: function(){
				return (this.info && this.info.items && !this.left());
			},
			left: function(){
				var left = 0;
				if(!this.info || !this.info.items) return left;
				for(var i in this.info.items) {
					if(!this.info.items[i].done) left++;
				}
				return left;
			},
			login: false
		},
		loadTemplate: function(name){
			this.state.left();
			return $.get('templates/'+name+'.mst');
		},
		showLogin: function(authError){
			$('.auth').show();
			$('.todoapp').hide();
			$('.main-actions').hide();

			if(authError) {
				$('#loginForm input[name=password]').focus().addClass('has-error');
			} else {
				$('.auth input').removeClass('has-error').first().focus();
			}
		},
		selectCurrentList: function(){
			var currentId = localStorage['currentList'];
			if( $('#currentList option[value="'+currentId+'"]').length ) $('#currentList').val(currentId);
			if(this.state.lists.length) $('#currentList').change();
		},
		showApp: function(){
			var pthis = this;

			$('.auth').hide();
			$('.todoapp')[this.state.lists.length ? 'show' : 'hide']();

			this.state.login = localStorage['login'];

			this.loadTemplate('actions').done(function(template){
				var html = Mustache.render(template, pthis.state);
				$('.main-actions').html(html).show();
				pthis.selectCurrentList();
			});
		},
		showList: function(){
			var pthis = this,
				todoapp = $('.todoapp');

			this.rest('GET', 'roster/info', {id: localStorage['currentList']}).done(function(data){
				pthis.state.info = data;
				if(!data) return false;

				pthis.loadTemplate('main').done(function(template){
					var html = Mustache.render(template, pthis.state);
					todoapp.html(html).show();
					$('.new-todo').focus();
					if(pthis.state.info.readonly) $('.todoapp .header').hide();
					else $('.todoapp .header').show();
					if(pthis.state.info.is_mine) {
						$('button.js-list-edit').show();
					} else {
						$('button.js-list-edit').hide();
					}
					if(todoapp.hasClass('show-completed')) $('.filters a[rel=completed]').click();
					if(todoapp.hasClass('show-active')) $('.filters a[rel=active]').click();
				});
			}).fail(function(xhr){
				todoapp.hide();
				if(xhr.status == 401) pthis.init();
			});
		},
		rest: function(method, path, data){
			return $.ajax({
				url: 'api.php?_path='+path,
				method: method,
				dataType: 'json',
				data: undefined !== data ? data : {},
				beforeSend: function (xhr) {
					if(localStorage['login'] && localStorage['password']) {
						xhr.setRequestHeader("Authorization", "Basic "+btoa(localStorage['login']+":"+localStorage['password']));
					}
				}
			});
		},
		init: function(authError){
			var pthis = this;

			this.bind();

			this.rest('GET', 'roster/list').done(function(data){
				pthis.state.lists = data;
				pthis.showApp();
			}).fail(function(){
				pthis.showLogin(authError);
			});
		},
		processListState: function() {
			var all = $('.todo-list li').length,
				left = $('.todo-list li:not(.completed)').length;
			$('.main .toggle-all').prop('checked', (all && !left));
			$('.todoapp .todo-count strong').text(left);
		},
		bind: function(){
			if(this.binded) return true;
			this.binded = true;

			var pthis = this;

			var checkForm = function(form){
				var success = true,
					data = {};

				$(form).find('.has-error').removeClass('has-error');

				$(form).find('input[type=text],input[type=password]').each(function(){
					var value = $.trim( $(this).val() );
					data[ $(this).attr('name') ] = value;
					if(value === '') {
						success = false;
						$(this).addClass('has-error');
					}
				});

				if(!success) {
					$(form).find('.has-error').first().focus();
					return false;
				}
				return data;
			};

			$('#joinForm').submit(function(){
				var form = this,
					data = checkForm(form);
				if(false === data) return false;

				pthis.rest('POST', 'user/join', data).done(function(resp){
					if(undefined !== resp['error'] && resp['error'] == 'Login is busy') {
						$(form).find('[name=login]').addClass('has-error').focus();
						return false;
					}
					localStorage['login'] = data.login;
					localStorage['password'] = data.password;
					pthis.init();
				});

				return false;
			});

			$('#loginForm').submit(function(){
				var data = checkForm(this);
				if(false === data) return false;

				localStorage['login'] = data.login;
				localStorage['password'] = data.password;
				pthis.init(true);
				return false;
			});

			$(document).on('click', '#logout', function(){
				delete localStorage['login'];
				delete localStorage['password'];
				$('.auth input').val('');
				pthis.init();
			});

			$(document).on('change', '#currentList', function(){
				localStorage['currentList'] = $(this).val();
				pthis.showList();
			});

			$(document).on('change', '.todo-list .toggle', function(){
				if(pthis.state.info.readonly) return false;

				var checked = $(this).is(':checked'),
					item = $(this).closest('li'),
					id = item.data('id');

				pthis.rest('PUT', 'item/done', {id: id, done: (checked?1:0)}).done(function(){
					item.toggleClass('completed', checked);
					pthis.processListState();
				}).fail(function(){
					pthis.init();
				});
			});

			$(document).on('dblclick', '.todo-list label', function(){
				if(pthis.state.info.readonly) return false;

				var item = $(this).closest('li'),
					input = item.find('.edit'),
					id = item.data('id');

				input.val(input.data('original'));
				item.addClass('editing');
				input.focus();
			});
			$(document).on('click', '.todo-list .edit', function(){
				return false;
			});
			var cancelEditing = function(){
				$('.todo-list .editing').removeClass('editing');
			};
			$(document).on('keydown', '.todo-list .edit', function(e){
				if(e.which == 27) cancelEditing();
				if(e.which == 13) {
					var value = $.trim( $(this).val() ),
						item = $(this).closest('li');
					if(value == '') return cancelEditing();
					item.find('label').text(value);
					item.removeClass('editing');
					pthis.rest('PUT', 'item/text', {id:item.data('id'), text:value}).done(function(){
						pthis.showList();
					}).fail(function(){
						pthis.init();
					});
				}
			});
			$(document).on('click', function(){
				cancelEditing();
			});

			$(document).on('keydown', '.new-todo', function(e){
				var value = $.trim( $(this).val() );
				if(e.which == 13 && value !== '') {
					$(this).val('');
					pthis.rest('POST', 'item/add', {roster_id: localStorage['currentList'], text: value}).done(function(){
						pthis.showList();
					}).fail(function(){
						pthis.init();
					});
				}
			});

			$(document).on('click', '.todo-list .destroy', function(){
				var item = $(this).closest('li'),
					id = item.data('id');
				item.remove();
				pthis.rest('DELETE', 'item/delete', {id: id}).done(function(){
					pthis.processListState();
				}).fail(function(){
					pthis.init();
				});
			});

			var toggleAction = function(action, value) {
				if(undefined === value) value = '';

				$('.action-editing,.action-default').hide();
				$('.action-'+action).show();
				if(action == 'editing') $('input[name=listName]').focus().val(value);
			};

			$(document).on('click', '#addList', function(){
				toggleAction('editing');
				$('#listName').get(0).editCallback = function(value){
					pthis.rest('POST', 'roster/create', {name: value}).done(function(data){
						localStorage['currentList'] = data.id;
						pthis.init();
					}).fail(function(){
						pthis.init();
					});
				};
				return false;
			});

			$(document).on('click', '#renameList', function(){
				var listName = $('#currentList option[value="'+$('#currentList').val()+'"]').text();
				toggleAction('editing', listName);
				$('#listName').get(0).editCallback = function(value){
					pthis.rest('PUT', 'roster/rename', {id:localStorage['currentList'], name: value}).always(function(){
						pthis.init();
					})
				};
				return false;
			});

			$(document).on('keydown', '#listName', function(e){
				if(e.which == 27) toggleAction('default');
				if(e.which == 13) {
					var name = $.trim( $(this).val() );
					if(name !== '') {
						$(this).get(0).editCallback(name);
					}
					toggleAction('default');
					return false;
				}
			});
			$(document).on('click', '#listName', function(){
				return false;
			});
			$(document).on('click', function(){
				toggleAction('default');
			});

			$(document).on('click', '#deleteList', function(){
				pthis.rest('DELETE', 'roster/delete', {id: localStorage['currentList']}).always(function(){
					pthis.init();
				});
			});

			$(document).on('click', '.filters a', function(){
				var rel = $(this).attr('rel');

				$('.todoapp')
					.toggleClass('show-completed', rel == 'completed')
					.toggleClass('show-active', rel == 'active');

				$('.filters a').removeClass('selected');
				$(this).addClass('selected');

				return false;
			});

			$(document).on('click', '.main .toggle-all', function(){
				var checked = $(this).is(':checked');
				$('.todo-list li'+(checked?':not(.completed)':'.completed')+' .toggle').click();
			});

			$(document).on('click', '.clear-completed', function(){
				$('.todo-list li.completed .destroy').click();
			})
		}
	};

	$(document).ready(function(){
		App.init();
	});

})(window);
