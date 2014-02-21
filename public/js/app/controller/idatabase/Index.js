Ext.define('icc.controller.idatabase.Index', {
	extend: 'Ext.app.Controller',
	models: ['idatabase.Index'],
	stores: ['idatabase.Index'],
	views: ['idatabase.Index.Grid', 'idatabase.Index.Add', 'idatabase.Index.Window'],
	controllerName: 'idatabaseIndex',
	actions: {
		add: '/idatabase/index/add',
		edit: '/idatabase/index/edit',
		remove: '/idatabase/index/remove',
		save: '/idatabase/index/save'
	},
	init: function() {
		var me = this;
		var controllerName = me.controllerName;

		if (controllerName == '') {
			Ext.Msg.alert('成功提示', '请设定controllerName');
			return false;
		}

		me.addRef([{
			ref: 'list',
			selector: me.controllerName + 'Grid'
		}, {
			ref: 'add',
			selector: me.controllerName + 'Add'
		}, {
			ref: 'edit',
			selector: me.controllerName + 'Edit'
		}]);

		var listeners = {};

		listeners[controllerName + 'Add button[action=submit]'] = {
			click: function(button) {
				var grid = me.getList();
				var store = grid.store;
				var form = button.up('form').getForm();
				if (form.isValid()) {
					form.submit({
						waitTitle: '系统提示',
						waitMsg: '系统处理中，请稍后……',
						success: function(form, action) {
							Ext.Msg.alert('成功提示', action.result.msg);
							form.reset();
							store.load();
						},
						failure: function(form, action) {
							Ext.Msg.alert('失败提示', action.result.msg);
						}
					});
				} else {
					Ext.Msg.alert('失败提示', '表单验证失败，请确认你填写的表单符合要求');
				}
			}
		};

		listeners[controllerName + 'Grid button[action=add]'] = {
			click: function(button) {
				var grid = button.up('gridpanel');
				var win = Ext.widget(controllerName + 'Add', {
					__PROJECT_ID__: grid.__PROJECT_ID__,
					__COLLECTION_ID__: grid.__COLLECTION_ID__,
					plugin: grid.plugin,
					__PLUGIN_ID__: grid.__PLUGIN_ID__,
					__PLUGIN_COLLECTION_ID__: grid.__PLUGIN_COLLECTION_ID__
				});
				win.show();
			}
		};

		listeners[controllerName + 'Grid button[action=remove]'] = {
			click: function(button) {
				var grid = button.up('gridpanel');
				var selections = grid.getSelectionModel().getSelection();
				if (selections.length > 0) {
					Ext.Msg.confirm('提示信息', '请确认是否要删除您选择的信息?', function(btn) {
						if (btn == 'yes') {
							var _id = [];
							for (var i = 0; i < selections.length; i++) {
								selection = selections[i];
								grid.store.remove(selection);
								_id.push(selection.get('_id'));
							}

							Ext.Ajax.request({
								url: me.actions.remove,
								params: {
									_id: Ext.encode(_id),
									__PROJECT_ID__: grid.__PROJECT_ID__,
									__COLLECTION_ID__: grid.__COLLECTION_ID__
								},
								scope: me,
								success: function(response) {
									var text = response.responseText;
									var json = Ext.decode(text);
									Ext.Msg.alert('提示信息', json.msg);
									if (json.success) {
										grid.store.load();
									}
								}
							});
						}
					}, me);
				} else {
					Ext.Msg.alert('提示信息', '请选择您要删除的项');
				}
			}
		};

		me.control(listeners);
	}
});