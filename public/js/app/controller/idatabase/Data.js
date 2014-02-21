Ext.define('icc.controller.idatabase.Data', {
	extend: 'Ext.app.Controller',
	models: [],
	stores: [],
	views: ['idatabase.Data.Main', 'idatabase.Data.Grid', 'idatabase.Data.Search', 'idatabase.Data.Add', 'idatabase.Data.Password', 'idatabase.Data.Edit', 'idatabase.Data.Field.2dfield', 'idatabase.Statistic.Combobox'],
	controllerName: 'idatabaseData',
	plugin: false,
	__PLUGIN_ID__: '',
	actions: {
		add: '/idatabase/data/add',
		edit: '/idatabase/data/edit',
		remove: '/idatabase/data/remove',
		save: '/idatabase/data/save'
	},
	refs: [{
		ref: 'projectTabPanel',
		selector: 'idatabaseProjectTabPanel'
	}],
	activeDataGrid: function() {
		return this.getProjectTabPanel().getActiveTab().down('idatabaseCollectionTabPanel').getActiveTab().down('idatabaseDataGrid') ? this.getProjectTabPanel().getActiveTab().down('idatabaseCollectionTabPanel').getActiveTab().down('idatabaseDataGrid') : this.getProjectTabPanel().getActiveTab().down('idatabaseCollectionTabPanel').getActiveTab().down('idatabaseDataTreeGrid');
	},
	init: function() {
		var me = this;
		var controllerName = me.controllerName;

		if (controllerName == '') {
			Ext.Msg.alert('成功提示', '请设定controllerName');
			return false;
		}

		me.addRef([{
			ref: 'main',
			selector: me.controllerName + 'Main'
		}, {
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
				var grid = me.activeDataGrid();
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

		listeners[controllerName + 'Edit button[action=submit]'] = {
			click: function(button) {
				var grid = me.activeDataGrid();
				var store = grid.store;
				var form = button.up('form').getForm();
				if (form.isValid()) {

					var htmleditors = button.up('form').query('htmleditor');
					if (Ext.isArray(htmleditors) && htmleditors.length > 0) {
						Ext.Array.forEach(htmleditors, function(item, index, allitems) {
							item.toggleSourceEdit(false);
						});
					}

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

		listeners['idatabaseDataGrid button[action=add],idatabaseDataTreeGrid button[action=add]'] = {
			click: function(button) {
				var grid = button.up('gridpanel') ? button.up('gridpanel') : button.up('treepanel');
				var win = Ext.widget(controllerName + 'Add', {
					__PROJECT_ID__: grid.__PROJECT_ID__,
					__COLLECTION_ID__: grid.__COLLECTION_ID__,
					__PLUGIN_ID__: grid.__PLUGIN_ID__,
					addOrEditFields: grid.addOrEditFields
				});
				win.show();
			}
		};

		listeners['idatabaseDataGrid button[action=edit],idatabaseDataTreeGrid button[action=edit]'] = {
			click: function(button) {
				var grid = button.up('gridpanel') ? button.up('gridpanel') : button.up('treepanel');
				var selections = grid.getSelectionModel().getSelection();
				if (selections.length > 1) {
					Ext.Msg.alert('提示信息', '请选择“一项”您要编辑的项目，请勿多选');
					return false;
				}
				if (selections.length === 1) {
					var win = Ext.widget(controllerName + 'Edit', {
						__PROJECT_ID__: grid.__PROJECT_ID__,
						__COLLECTION_ID__: grid.__COLLECTION_ID__,
						__PLUGIN_ID__: grid.__PLUGIN_ID__,
						addOrEditFields: grid.addOrEditFields
					});

					var convertDot = function(name) {
						return name.replace(/__DOT__/g, '.');
					};

					var form = win.down('form').getForm();
					form.loadRecord(selections[0]);
					Ext.Array.forEach(grid.addOrEditFields, function(item, index) {
						//转换处理dot
						var field = '';
						var sourceField = '';
						if (item.name != undefined) {
							field = item.name;
						} else if (item.radioName != undefined) {
							field = item.radioName;
						} else if (item.fieldName != undefined) {
							field = item.fieldName;
						}
						sourceField = convertDot(field);

						if (item.xtype == '2dfield') {
							var tmp = selections[0].get(field).split(',');
							form.findField(field + '[lng]').setValue(tmp[0]);
							form.findField(field + '[lat]').setValue(tmp[1]);
							return true;
						} else if (item.xtype == 'boolfield') {
							var fieldValue = selections[0].get(field);
							fieldValue = Ext.isBoolean(fieldValue) ? fieldValue : false;
							if (fieldValue === true) {
								form.findField(field).setValue(true);
							} else {
								form.findField(field).next().setValue(true);
							}
						} else if (item.xtype == 'boxselect') {
							var boxSelect = form.findField(field);
							var fieldValue = selections[0].get(field.replace("[]", ''));
							if (Ext.isArray(fieldValue)) {
								boxSelect.setValue(fieldValue);
							} else {
								fieldValue = Ext.JSON.decode(fieldValue, true);
								if (fieldValue != null) {
									boxSelect.setValue(fieldValue);
								} else {
									console.log('boxSelect fieldValue is not a json string');
								}
							}

						} else {
							form.findField(field).setValue(selections[0].get(sourceField));
						}
						return true;
					});
					win.show();
				} else {
					Ext.Msg.alert('提示信息', '请选择你要编辑的项');
				}
				return true;
			}
		};

		listeners['idatabaseDataGrid button[action=save],idatabaseDataTreeGrid button[action=save]'] = {
			click: function(button) {
				var grid = button.up('gridpanel') ? button.up('gridpanel') : button.up('treepanel');
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
						updateInfos: Ext.encode(updateList),
						__PROJECT_ID__: grid.__PROJECT_ID__,
						__COLLECTION_ID__: grid.__COLLECTION_ID__,
						__PLUGIN_ID__: grid.__PLUGIN_ID__
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

		listeners['idatabaseDataGrid button[action=remove],idatabaseDataTreeGrid button[action=remove]'] = {
			click: function(button) {
				var grid = button.up('gridpanel') ? button.up('gridpanel') : button.up('treepanel');
				var selections = grid.getSelectionModel().getSelection();
				if (selections.length > 0) {
					Ext.Msg.confirm('提示信息', '请确认是否要删除您选择的信息?', function(btn) {
						if (btn == 'yes') {
							var _id = [];
							for (var i = 0; i < selections.length; i++) {
								selection = selections[i];
								_id.push(selection.get('_id'));
							}

							Ext.Ajax.request({
								url: me.actions.remove,
								params: {
									_id: Ext.encode(_id),
									__PROJECT_ID__: grid.__PROJECT_ID__,
									__COLLECTION_ID__: grid.__COLLECTION_ID__,
									__PLUGIN_ID__: grid.__PLUGIN_ID__
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

		listeners['idatabaseDataGrid button[action=drop],idatabaseDataTreeGrid button[action=drop]'] = {
			click: function(button) {
				var grid = button.up('gridpanel') ? button.up('gridpanel') : button.up('treepanel');
				var selections = grid.getSelectionModel().getSelection();
				Ext.Msg.confirm('安全警告', '您当前执行的是清空操作，清空后数据将无法找回，请确认您是否要清空全部数据?', function(btn) {
					if (btn == 'yes') {
						var win = Ext.widget(controllerName + 'Password', {
							__PROJECT_ID__: grid.__PROJECT_ID__,
							__COLLECTION_ID__: grid.__COLLECTION_ID__,
							__PLUGIN_ID__: grid.__PLUGIN_ID__,
							width: 320,
							height: 240
						});
						win.show();
					}
				}, me);
			}
		};

		listeners['idatabaseDataPassword button[action=submit]'] = {
			click: function(button) {
				var grid = me.activeDataGrid();
				var store = grid.store;
				var form = button.up('form').getForm();
				if (form.isValid()) {
					form.submit({
						waitTitle: '系统提示',
						waitMsg: '系统处理中，请稍后……',
						success: function(form, action) {
							Ext.Msg.alert('成功提示', action.result.msg);
							button.up('window').close();
							store.load();
						},
						failure: function(form, action) {
							Ext.Msg.alert('失败提示', action.result.msg);
						}
					});
				}
			}
		};

		listeners['idatabaseDataSearch button[action=search],button[action=excel]'] = {
			click: function(button) {
				var form = button.up('form').getForm();
				if (form.isValid()) {
					var extraParams = form.getValues(false, true);
					var store = me.activeDataGrid().store;
					form.getFields().each(function(items, index) {
						if (items.xtype != 'hiddenfield') {
							delete store.proxy.extraParams[items.name];
						}
					});

					store.proxy.extraParams.action = button.action;
					store.proxy.extraParams.start = 0;
					store.proxy.extraParams = Ext.Object.merge(store.proxy.extraParams, extraParams);

					if (button.action == 'excel') {
						Ext.Msg.confirm('系统提示', '导出数据有可能需要较长的时间，请点击“导出”按钮后，耐心等待，两次操作间隔需大于30秒！', function(btn) {
							if (btn == 'yes') {
								button.setDisabled(true);
								setTimeout(function() {
									button.setDisabled(false);
								}, 30000);
								window.location.href = '/idatabase/data/index?' + Ext.Object.toQueryString(store.proxy.extraParams);
							}
						}, me);
					} else {
						button.setDisabled(true);
						setTimeout(function() {
							button.setDisabled(false);
						}, 3000);
					}
					store.load(function(records, operation, success) {
						if (success) {
							button.setDisabled(false);
						}
					});
				}
			}
		};

		listeners['idatabaseDataSearch button[action=statistic]'] = {
			click: function(button) {
				button.setDisabled(true);
				setTimeout(function() {
					button.setDisabled(false);
				}, 30000);

				var form = button.up('form').getForm();
				var grid = me.activeDataGrid();
				var __STATISTIC_ID__ = form.findField('__STATISTIC_ID__').getValue();
				if (__STATISTIC_ID__ == null || __STATISTIC_ID__ == '' || __STATISTIC_ID__ == undefined) {
					Ext.Msg.alert('提示信息', '请选择统计方法');
					button.setDisabled(false);
					return false;
				}

				var store = Ext.create('icc.store.idatabase.Statistic.One');
				store.proxy.extraParams = {
					__PROJECT_ID__: grid.__PROJECT_ID__,
					__COLLECTION_ID__: grid.__COLLECTION_ID__,
					__STATISTIC_ID__: __STATISTIC_ID__
				};

				store.load(function(records, operation, success) {
					if (success) {
						if (records.length > 0) {
							var win = Ext.widget('idatabaseStatisticChart', {
								__PROJECT_ID__: grid.__PROJECT_ID__,
								__COLLECTION_ID__: grid.__COLLECTION_ID__,
								__PLUGIN_ID__: grid.__PLUGIN_ID__,
								__STATISTIC_ID__: grid.__STATISTIC_ID__,
								__STATISTIC_INFO__: records[0],
								__EXTRAPARAMS__: form.getValues(false, true),
								__BUTTON__ : button,
								width: 640,
								height: 480
							});
							win.show();
						} else {
							Ext.Msg.alert('提示信息', '无效的统计方法');
							button.setDisabled(false);
							return false;
						}
					} else {
						console.info(success, records);
					}
					return true;
				});
				return true;
			}
		};


		me.control(listeners);
		return true;
	}
});