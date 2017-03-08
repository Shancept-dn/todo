(function (window) {
	'use strict';

	/**
	 * Хранит состояние для отображения
	 */
	var state = {
		lists: [], //Списки пользователя
		info: false, //Информация о текущем списке
		/**
		 * Возвращает выполнены ли все дела в списке (Helper для mustache)
		 * @returns {boolean}
		 */
		allDone: function(){
			return (this.info && this.info.items && !this.left());
		},
		/**
		 * Сколько дел осталось сделать (Helper для mustache)
		 * @returns {number}
		 */
		left: function(){
			var left = 0;
			if(!this.info || !this.info.items) return left;
			for(var i in this.info.items) {
				if(!this.info.items[i].done) left++;
			}
			return left;
		},
		login: false //Текущий логин пользователя
	};

	/**
	 * Загружает шаблон mustache
	 * @param {string} name
	 * @returns {Deferred}
	 */
	var loadTemplate = function(name){
		return $.get('templates/'+name+'.mst');
	};

	/**
	 * Показывает окно авторизации
	 * @param {bool} authError отобразить ошибку авторизации
	 */
	var showLoginWindow = function(authError){
		$('.auth').show();
		$('.shares').hide();
		$('.todoapp').hide();
		$('.main-actions').hide();

		if(authError) {
			$('#loginForm input[name=password]').focus().addClass('has-error');
		} else {
			$('.auth input').removeClass('has-error').first().focus();
		}
	};

	/**
	 * Прказывает основное окно приложения (header, список)
	 */
	var showAppWindow = function(){
		$('.auth').hide();
		$('.shares').hide();
		$('.todoapp')[state.lists.length ? 'show' : 'hide']();

		loadTemplate('actions').done(function(template){
			//Рендерим html
			var html = Mustache.render(template, state);
			$('.main-actions').html(html).show();

			//Автоматически выбираем список (прошлый, или первый попавшийся)
			var currentId = localStorage['currentList'];
			if( $('#currentList option[value="'+currentId+'"]').length ) $('#currentList').val(currentId);
			if(state.lists.length) $('#currentList').change();
		});
	};

	/**
	 * Выполнение REST запросов в API
	 * @param {string} method GET,POST,PUT,DELETE
	 * @param {string} path путь
	 * @param {object|undefined} data
	 * @returns {Deferred}
	 */
	var rest = function(method, path, data){
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
	};

	/**
	 * Инициализирует приложение
	 * @param authError отобразить ошибку авторизации в случае неудачи
	 */
	var init = function(authError){
		//Пробуем получить списки
		rest('GET', 'roster/list').done(function(data){
			//Если удалось - запоминаем данные и отображаем приложение
			state.lists = data;
			state.login = localStorage['login'];
			showAppWindow();
		}).fail(function(){
			//Если не удалось отображаем окно авторизации
			showLoginWindow(authError);
		});
	};

	/**
	 * Получает данные и отображает текущий выбранный список
	 */
	var showList = function(){
		var todoapp = $('.todoapp');

		/**
		 * Получаем данные списка
		 */
		rest('GET', 'roster/info', {id: localStorage['currentList']}).done(function(data){
			state.info = data;
			if(!data) return false;

			loadTemplate('main').done(function(template){
				var html = Mustache.render(template, state);
				todoapp.html(html).show();

				$('.new-todo').focus();
				//Если список readonly - скрываем ввод новых дел
				if(state.info.readonly) $('.todoapp .header').hide();
				else $('.todoapp .header').show();
				//Если список собственный отображаем кпоки редактирования списка (Rename/Share/Delete)
				if(state.info.is_mine) {
					$('button.js-list-edit').show();
				} else {
					$('button.js-list-edit').hide();
				}
				//Восстанавливаем предыдущее состояние фильтра All/Active/Completed
				if(todoapp.hasClass('show-completed')) $('.filters a[rel=completed]').click();
				if(todoapp.hasClass('show-active')) $('.filters a[rel=active]').click();
			});
		}).fail(function(xhr){
			todoapp.hide();
			if(xhr.status == 401) init();
		});
	};

	/**
	 * Подсчитывает и отображает сколько дел осталось и меняет состояние главного checkbox для всех дел
	 */
	var processListState = function() {
		var all = $('.todo-list li').length,
			left = $('.todo-list li:not(.completed)').length;
		$('.main .toggle-all').prop('checked', (all && !left));
		$('.todoapp .todo-count strong').text(left);
	};

	/**
	 * Проверяет заполненность формы и подсвечивает ошибки, если всё ок - возвращает данные формы
	 * @param {object} form
	 * @returns {object|bool}
	 */
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

	/**
	 * ****** Привязываем события на элементы ******
	 */

	/**
	 * Submit формы регистрации
 	 */
	$('#joinForm').submit(function(){
		var form = this,
			data = checkForm(form);
		if(false === data) return false;

		//Регистрируем пользователя
		rest('POST', 'user/join', data).done(function(resp){
			//Если логин занят - подсвечиваем input
			if(undefined !== resp['error'] && resp['error'] == 'Login is busy') {
				$(form).find('[name=login]').addClass('has-error').focus();
				return false;
			}

			//Запоминаем данные и инициализируем приложение
			localStorage['login'] = data.login;
			localStorage['password'] = data.password;
			init();
		});

		return false;
	});

	/**
	 * Submit формы авторизации
	 */
	$('#loginForm').submit(function(){
		var data = checkForm(this);
		if(false === data) return false;

		//Запоминаем данные и пытаемся проинициализировать приложение
		localStorage['login'] = data.login;
		localStorage['password'] = data.password;
		init(true);
		return false;
	});

	/**
	 * Кнопка выход
	 */
	$(document).on('click', '#logout', function(){
		//Стираем запомненные данные
		delete localStorage['login'];
		delete localStorage['password'];
		$('.auth input').val('');
		//Инициализируем приложение - будет отображена форма регистрации/авторизации
		init();
	});

	/**
	 * Выбрали новый список
	 */
	$(document).on('change', '#currentList', function(){
		localStorage['currentList'] = $(this).val();
		showList();
	});

	/**
	 * Изменили состояние дела сделано/не сделано
	 */
	$(document).on('change', '.todo-list .toggle', function(){
		if(state.info.readonly) return false;

		var checked = $(this).is(':checked'),
			item = $(this).closest('li'),
			id = item.data('id');

		rest('PUT', 'item/done', {id: id, done: (checked?1:0)}).done(function(){
			item.toggleClass('completed', checked);
			processListState();
		}).fail(function(){
			init();
		});
	});

	/**
	 * Двойной клик по названию дела - отобразить редактирование
	 */
	$(document).on('dblclick', '.todo-list label', function(){
		if(state.info.readonly) return false;

		var item = $(this).closest('li'),
			input = item.find('.edit'),
			id = item.data('id');

		input.val(input.data('original'));
		item.addClass('editing');
		input.focus();
	});

	/**
	 * При клике по input редактирования останавливаем высплывание события
	 */
	$(document).on('click', '.todo-list .edit', function(){
		return false;
	});

	/**
	 * Отменить редактирование дела
	 */
	var cancelEditing = function(){
		$('.todo-list .editing').removeClass('editing');
	};

	/**
	 * На нажатие кнопки в поле редактирования дела
	 */
	$(document).on('keydown', '.todo-list .edit', function(e){
		//Escape - отменить редактирование
		if(e.which == 27) cancelEditing();
		//Enter - отправляем данные в API
		if(e.which == 13) {
			var value = $.trim( $(this).val() ),
				item = $(this).closest('li');
			if(value == '') return cancelEditing();
			//Применяем новый текст
			item.find('label').text(value);
			item.removeClass('editing');
			//Отправляем данные в API
			rest('PUT', 'item/text', {id:item.data('id'), text:value}).done(function(){
				showList();
			}).fail(function(){
				init();
			});
		}
	});

	/**
	 * Кликнули по документу - отменяем редактирование дела
	 */
	$(document).on('click', function(){
		cancelEditing();
	});

	/**
	 * Нажатие кнопки в поле нового дела
	 */
	$(document).on('keydown', '.new-todo', function(e){
		var value = $.trim( $(this).val() );
		//Enter - отправляем данные в API и заново загружаем/отображаем список
		if(e.which == 13 && value !== '') {
			$(this).val('');
			rest('POST', 'item/add', {roster_id: localStorage['currentList'], text: value}).done(function(){
				showList();
			}).fail(function(){
				init();
			});
		}
	});

	/**
	 * Кнопка "удалить" дело
	 */
	$(document).on('click', '.todo-list .destroy', function(){
		var item = $(this).closest('li'),
			id = item.data('id');

		//Удаляем DOM-элемент
		item.remove();

		//Отправляем данные в API
		rest('DELETE', 'item/delete', {id: id}).done(function(){
			processListState();
		}).fail(function(){
			init();
		});
	});

	/**
	 * Переключает состояние в шапке (editing - оторазить поле ввода, default - по у молчанию)
	 * @param {string} action
	 * @param {undefined|string} value если action='editing' - установить значение в поле ввода
	 */
	var toggleAction = function(action, value) {
		if(undefined === value) value = '';

		$('.action-editing,.action-default').hide();
		$('.action-'+action).show();
		if(action == 'editing') $('input[name=listName]').focus().val(value);
	};

	/**
	 * Кнопка добавить список
	 */
	$(document).on('click', '#addList', function(){
		toggleAction('editing');

		//Callback при нажатии Enter в поле ввода
		$('#listName').get(0).editCallback = function(value){
			//Отправляем данные в API
			rest('POST', 'roster/create', {name: value}).done(function(data){
				//Запоминаем ID нового списка как текущий выбранный
				localStorage['currentList'] = data.id;
				//Инициализируем приложение
				init();
			}).fail(function(){
				init();
			});
		};
		return false;
	});

	/**
	 * Кнопка переименовать список
	 */
	$(document).on('click', '#renameList', function(){
		//Название текущего выбранного списка в select
		var listName = $('#currentList option[value="'+$('#currentList').val()+'"]').text();

		toggleAction('editing', listName);

		//Callback при нажатии Enter в поле ввода
		$('#listName').get(0).editCallback = function(value){
			//Отправляем данные в API
			rest('PUT', 'roster/rename', {id:localStorage['currentList'], name: value}).always(function(){
				init();
			})
		};
		return false;
	});

	/**
	 * Нажатие кнопки в поле ввода редактирования названия списка
	 */
	$(document).on('keydown', '#listName', function(e){
		//Escape - отменить редактирование
		if(e.which == 27) toggleAction('default');
		//Enter - получаем значение поля ввода и передаем в установленный ранее callback
		if(e.which == 13) {
			var name = $.trim( $(this).val() );
			if(name !== '') {
				$(this).get(0).editCallback(name);
			}
			toggleAction('default');
			return false;
		}
	});

	/**
	 * Клик по полю ввода - предотвращаем всплывание события
	 */
	$(document).on('click', '#listName', function(){
		return false;
	});

	/**
	 * Клик по документу - отменяем редактирование
	 */
	$(document).on('click', function(){
		toggleAction('default');
	});

	/**
	 * Кнопка удалить список
	 */
	$(document).on('click', '#deleteList', function(){
		//Отправляем данные на сервер
		rest('DELETE', 'roster/delete', {id: localStorage['currentList']}).always(function(){
			//Заново инициализируем приложение
			init();
		});
	});

	/**
	 * Клик по фильтрам All/Active/Complete
	 */
	$(document).on('click', '.filters a', function(){
		//По какому фильтру кликнули
		var rel = $(this).attr('rel');

		//Меняем класс для списка дел
		$('.todoapp')
			.toggleClass('show-completed', rel == 'completed')
			.toggleClass('show-active', rel == 'active');

		//Текущий фильтр делаем selected
		$('.filters a').removeClass('selected');
		$(this).addClass('selected');

		return false;
	});

	/**
	 * Клик по кнопке отметить/снять все задачи
	 */
	$(document).on('click', '.main .toggle-all', function(){
		var checked = $(this).is(':checked');
		//Производим клик по всем активным/завершенным задачам
		$('.todo-list li'+(checked?':not(.completed)':'.completed')+' .toggle').click();
	});

	/**
	 * Кнопка удалить завершенные задачи
	 */
	$(document).on('click', '.clear-completed', function(){
		//Выполняем клик по кноопку удаления всех незавершенных задач
		$('.todo-list li.completed .destroy').click();
	});

	/**
	 * Показать окно с данными расшаривания текущего списка
	 * @param {boolean} reload перечитать данные из АПИ
	 * @returns {boolean}
	 */
	var showShares = function(reload){
		if(reload) {
			rest('GET', 'roster/info', {id: localStorage['currentList']}).done(function(data){
				state.info = data;
				showShares();
			}).fail(function(){
				init();
			});
			return false;
		}

		$('.shares').show();
		loadTemplate('shares').done(function(template){
			//Рендерим html
			var html = Mustache.render(template, state);
			$('.shares-data-container').html(html).show();
		});
	};

	/**
	 * Typeahead поиск пользователей по логину
	 */
	$('#searchUsers').typeahead({
		hint: true,
		highlight: true,
		minLength: 1
	},{
		async: true,
		source: function (query, pSync, pAsync) {
			return rest('GET', 'user/search', {query:query}).done(function(req){
				//Запоминаем полученные данные
				$('#searchUsers').get(0).qResults = {};
				//Формируем список для typeahead
				var list = [];
				for(var i in req) {
					$('#searchUsers').get(0).qResults[req[i].login] = req[i].id;
					list.push(req[i].login);
				}
				return pAsync(list);
			});
		}
	});

	/**
	 * Кнопка Share
	 */
	$(document).on('click', '#shareList', function(){
		showShares();
	});

	/**
	 * Сменить состояние readonly
	 */
	$(document).on('change', '.js-change-share', function(){
		var share = $(this).closest('[data-id]'),
			id = share.data('id'),
			readonly = $(this).is(':checked');

		rest('PUT', 'roster/share', {id: localStorage['currentList'], user_id: id, readonly: readonly?1:0}).always(function(){
			showShares(true);
		});
	});

	/**
	 * Удалить расшаривание пользователю
	 */
	$(document).on('click', '.js-delete-share', function(){
		var share = $(this).closest('[data-id]'),
			id = share.data('id');

		rest('DELETE', 'roster/share', {id: localStorage['currentList'], user_id: id}).always(function(){
			showShares(true);
		});
	});

	/**
	 * Клик по окну расшаривания - предотвращаем всплывание событие
	 */
	$('.shares-data').click(function(){
		return false;
	});

	/**
	 * Клик по bg - скрыть окно расшаривания
	 */
	$('.shares').click(function(){
		$(this).hide();
	});

	/**
	 * Нажали Escape - скрыть окно расшаривания
	 */
	$(document).on('keydown', function(e){
		if(e.which == 27) $('.shares').hide();
	});

	/**
	 * Расшарить пользователя
	 * @param {object} input поле ввода логина пользователя
	 * @returns {boolean}
	 */
	var shareUser = function(input){
		var value = $(input).typeahead('val'),
			id = $(input).get(0).qResults[value];
		if(!id) return false;
		$(input).typeahead('val', '');

		rest('PUT', 'roster/share', {id: localStorage['currentList'], user_id: id}).always(function(){
			showShares(true);
		});
	};

	/**
	 * Enter в поле ввода логина пользователя
	 */
	$('#searchUsers').keydown(function(e){
		if(e.which == 13) {
			shareUser($(this));
		}
	});

	/**
	 * Клик по кнопке расшарить
	 */
	$('#shareUserBtn').click(function(){
		shareUser($('#searchUsers'));
	});

	//Инициализируем приложение
	$(document).ready(function(){
		init();
	});

})(window);
