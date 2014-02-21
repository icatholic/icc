Ext.define('icc.controller.idatabase.Structure', {
	extend: 'Ext.app.Controller',
	models: ['idatabase.Structure', 'idatabase.Structure.FilterType'],
	stores: ['idatabase.Structure', 'idatabase.Structure.Type', 'idatabase.Structure.RshType', 'idatabase.Structure.FilterType', 'idatabase.Collection.All'],
	views: ['idatabase.Collection.AllCombobox', 'idatabase.Structure.Grid', 'idatabase.Structure.Add', 'idatabase.Structure.Edit', 'idatabase.Structure.Window', 'idatabase.Structure.FilterCombobox'],
	controllerName: 'idatabaseStructure',
	actions: {
		add: '/idatabase/structure/add',
		edit: '/idatabase/structure/edit',
		remove: '/idatabase/structure/remove',
		save: '/idatabase/structure/save'
	},
	refs: [{
		ref: 'projectTabPanel',
		selector: 'idatabaseProjectTabPanel'
	}],
	activeTabGrid: function(gridName) {
		return this.getProjectTabPanel().getActiveTab().down(gridName);
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
							store.load(function(records) {
								form.findField('orderBy').setValue(store.getTotalCount());
							});
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

		listeners[controllerName + 'Edit button[action=submit]'] = {
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
							store.load();
						},
						failure: function(form, action) {
							Ext.Msg.alert('失败提示', action.result.msg);
						}
					});
				}
			}
		};

		listeners[controllerName + 'Grid button[action=add]'] = {
			click: function(button) {
				var grid = button.up('gridpanel');
				var orderBy = grid.store.getTotalCount();
				var win = Ext.widget(controllerName + 'Add', {
					__PROJECT_ID__: grid.__PROJECT_ID__,
					__COLLECTION_ID__: grid.__COLLECTION_ID__,
					__PLUGIN_ID__: grid.__PLUGIN_ID__,
					__PLUGIN_COLLECTION_ID__: grid.__PLUGIN_COLLECTION_ID__,
					orderBy: orderBy
				});
				win.show();
			}
		};

		listeners[controllerName + 'Grid button[action=edit]'] = {
			click: function(button) {
				var grid = button.up('gridpanel');
				var selections = grid.getSelectionModel().getSelection();
				if (selections.length > 0) {
					var win = Ext.widget(controllerName + 'Edit', {
						__PROJECT_ID__: grid.__PROJECT_ID__,
						__COLLECTION_ID__: grid.__COLLECTION_ID__,
						__PLUGIN_ID__: grid.__PLUGIN_ID__,
						__PLUGIN_COLLECTION_ID__: grid.__PLUGIN_COLLECTION_ID__
					});
					var form = win.down('form').getForm();
					form.loadRecord(selections[0]);
					win.show();
				} else {
					Ext.Msg.alert('提示信息', '请选择你要编辑的项');
				}
			}
		};

		listeners[controllerName + 'Grid button[action=save]'] = {
			click: function(button) {
				var grid = button.up('gridpanel');
				var store = grid.store;
				var records = grid.store.getUpdatedRecords();
				var recordsNumber = records.length;
				if (recordsNumber == 0) {
					Ext.Msg.alert('提示信息', '很遗憾，未发现任何被修改的信息需要保存');
					return false;
				}
				var updateList = [];
				for (var i = 0; i < recordsNumber; i++) {
					record = records[i];
					updateList.push(record.data);
				}

				Ext.Ajax.request({
					url: me.actions.save,
					params: {
						__PROJECT_ID__: grid.__PROJECT_ID__,
						__COLLECTION_ID__: grid.__COLLECTION_ID__,
						__PLUGIN_COLLECTION_ID__: grid.__PLUGIN_COLLECTION_ID__,
						updateInfos: Ext.encode(updateList)
					},
					scope: me,
					success: function(response) {
						var text = response.responseText;
						var json = Ext.decode(text);
						Ext.Msg.alert('提示信息', json.msg);
						if (json.success) {
							store.load();
						}
					}
				});
				return true;
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
									__COLLECTION_ID__: grid.__COLLECTION_ID__,
									__PLUGIN_COLLECTION_ID__: grid.__PLUGIN_COLLECTION_ID__
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