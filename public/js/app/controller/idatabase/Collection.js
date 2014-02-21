Ext.define('icc.controller.idatabase.Collection', {
	extend: 'Ext.app.Controller',
	models: ['idatabase.Collection', 'idatabase.Structure'],
	stores: ['idatabase.Collection', 'idatabase.Collection.Type', 'idatabase.Structure'],
	views: ['idatabase.Collection.Grid', 'idatabase.Collection.Add', 'idatabase.Collection.Edit', 'idatabase.Collection.TabPanel', 'idatabase.Collection.Password', 'idatabase.Collection.Dashboard'],
	controllerName: 'idatabaseCollection',
	plugin: false,
	__PLUGIN_ID__: '',
	actions: {
		add: '/idatabase/collection/add',
		edit: '/idatabase/collection/edit',
		remove: '/idatabase/collection/remove',
		save: '/idatabase/collection/save'
	},
	refs: [{
		ref: 'projectTabPanel',
		selector: 'idatabaseProjectTabPanel'
	}],
	collectionTabPanel: function() {
		return this.getProjectTabPanel().getActiveTab().down('idatabaseCollectionTabPanel');
	},
	getExpandedAccordion: function() {
		return this.getProjectTabPanel().getActiveTab().down('idatabaseCollectionAccordion').child("[collapsed=false]");
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
				var grid = me.getExpandedAccordion();
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
				var grid = me.getExpandedAccordion();
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
				var win = Ext.widget(controllerName + 'Add', {
					__PROJECT_ID__: grid.__PROJECT_ID__,
					plugin: grid.plugin,
					__PLUGIN_ID__: grid.__PLUGIN_ID__,
					orderBy: grid.store.getTotalCount()
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
						plugin: grid.plugin,
						__PLUGIN_ID__: grid.__PLUGIN_ID__
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
						__PROJECT_ID__: grid.__PROJECT_ID__
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
									plugin: grid.plugin,
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

		listeners[controllerName + 'Grid'] = {
			selectionchange: function(selectionModel, selected, eOpts) {
				var grid = this.getExpandedAccordion();
				if (selected.length > 1) {
					Ext.Msg.alert('提示信息', '请勿选择多项');
					return false;
				}

				var record = selected[0];
				if (record) {
					var panel = this.collectionTabPanel().getComponent(record.get('_id'));
					if (record.get('locked') && panel == null) {
						var win = Ext.widget(controllerName + 'Password', {
							__PROJECT_ID__: grid.__PROJECT_ID__,
							__COLLECTION_ID__: record.get('_id'),
							width: 320,
							height: 240,
							selectedRecord: record
						});
						win.show();
					} else {
						this.buildDataPanel(grid, this.collectionTabPanel(), record);
					}
				}
				return true;
			}
		};

		listeners['idatabaseCollectionPassword button[action=submit]'] = {
			click: function(button) {
				var grid = this.getExpandedAccordion();
				var form = button.up('form').getForm();
				var win = button.up('window');
				if (form.isValid()) {
					form.submit({
						waitTitle: '系统提示',
						waitMsg: '系统处理中，请稍后……',
						success: function(form, action) {
							win.close();
							me.buildDataPanel(grid, me.collectionTabPanel(), grid.getSelectionModel().getSelection()[0]);
						},
						failure: function(form, action) {
							Ext.Msg.alert('失败提示', action.result.msg);
						}
					});
				}
			}
		};

		listeners[controllerName + 'Grid button[action=structure]'] = {
			click: function(button) {
				var grid = button.up('gridpanel');
				var selections = grid.getSelectionModel().getSelection();
				if (selections.length == 1) {
					var record = selections[0];
					var win = Ext.widget('idatabaseStructureWindow', {
						__PROJECT_ID__: grid.__PROJECT_ID__,
						__COLLECTION_ID__: record.get('_id'),
						plugin: grid.plugin,
						__PLUGIN_ID__: grid.__PLUGIN_ID__,
						__PLUGIN_COLLECTION_ID__: record.get('plugin_collection_id')
					});
					win.show();
				} else {
					Ext.Msg.alert('提示信息', '请选择一项您要编辑的集合');
				}
				return true;
			}
		};

		listeners[controllerName + 'Grid button[action=orderBy]'] = {
			click: function(button) {
				var grid = button.up('gridpanel');
				var selections = grid.getSelectionModel().getSelection();
				if (selections.length == 1) {
					var record = selections[0];
					var win = Ext.widget('idatabaseCollectionOrderWindow', {
						__PROJECT_ID__: grid.__PROJECT_ID__,
						__COLLECTION_ID__: record.get('_id'),
						plugin: grid.plugin,
						__PLUGIN_ID__: grid.__PLUGIN_ID__,
						__PLUGIN_COLLECTION_ID__: record.get('plugin_collection_id')
					});
					win.show();
				} else {
					Ext.Msg.alert('提示信息', '请选择一项您要编辑的集合');
				}
				return true;
			}
		};

		listeners[controllerName + 'Grid button[action=index]'] = {
			click: function(button) {
				var grid = button.up('gridpanel');
				var selections = grid.getSelectionModel().getSelection();
				if (selections.length == 1) {
					var record = selections[0];
					var win = Ext.widget('idatabaseIndexWindow', {
						__PROJECT_ID__: grid.__PROJECT_ID__,
						__COLLECTION_ID__: record.get('_id'),
						__PLUGIN_ID__: grid.__PLUGIN_ID__,
						plugin: grid.plugin,
						__PLUGIN_COLLECTION_ID__: record.get('plugin_collection_id')
					});
					win.show();
				} else {
					Ext.Msg.alert('提示信息', '请选择一项您要编辑的集合');
				}
				return true;
			}
		};

		listeners[controllerName + 'Grid button[action=mapping]'] = {
			click: function(button) {
				var grid = button.up('gridpanel');
				var selections = grid.getSelectionModel().getSelection();
				if (selections.length == 1) {
					var record = selections[0];
					var __PROJECT_ID__ = grid.__PROJECT_ID__;
					var __COLLECTION_ID__ = record.get('_id');
					var __PLUGIN_ID__ = grid.__PLUGIN_ID__;
					Ext.Ajax.request({
						url: '/idatabase/mapping/index',
						params: {
							__PROJECT_ID__: __PROJECT_ID__,
							__COLLECTION_ID__: __COLLECTION_ID__,
							__PLUGIN_ID__: __PLUGIN_ID__
						},
						scope: me,
						success: function(response) {
							var text = response.responseText;
							var json = Ext.decode(text);
							var collection = '';
							var database = 'ICCv1';
							var cluster = 'default';
							var active = false;

							if (json.total > 0) {
								collection = json.result[0].collection;
								database = json.result[0].database;
								cluster = json.result[0].cluster;
								active = json.result[0].active;
							}

							var win = Ext.widget('idatabaseMappingWindow', {
								__PROJECT_ID__: __PROJECT_ID__,
								__COLLECTION_ID__: __COLLECTION_ID__,
								collection: collection,
								database: database,
								cluster: cluster,
								active: active
							});
							win.show();
						}
					});
				} else {
					Ext.Msg.alert('提示信息', '请选择一项您要添加映射关系的集合');
				}
				return true;
			}
		};

		listeners[controllerName + 'Grid button[action=statistic]'] = {
			click: function(button) {
				var grid = button.up('gridpanel');
				var selections = grid.getSelectionModel().getSelection();
				if (selections.length == 1) {
					var record = selections[0];
					var win = Ext.widget('idatabaseStatisticWindow', {
						__PROJECT_ID__: grid.__PROJECT_ID__,
						__COLLECTION_ID__: record.get('_id'),
						__PLUGIN_ID__: grid.__PLUGIN_ID__,
						plugin: grid.plugin,
						__PLUGIN_COLLECTION_ID__: record.get('plugin_collection_id')
					});
					win.show();
				} else {
					Ext.Msg.alert('提示信息', '请选择一项您要添加统计信息的集合');
				}
				return true;
			}
		};

		listeners[controllerName + 'Grid button[action=lock]'] = {
			click: function(button) {
				var grid = button.up('gridpanel');
				var selections = grid.getSelectionModel().getSelection();
				if (selections.length == 1) {
					var record = selections[0];
					var win = Ext.widget('idatabaseLockWindow', {
						__PROJECT_ID__: grid.__PROJECT_ID__,
						__COLLECTION_ID__: record.get('_id'),
						__PLUGIN_ID__: grid.__PLUGIN_ID__
					});
					win.show();
				} else {
					Ext.Msg.alert('提示信息', '请选择一项您要编辑的集合');
				}
				return true;
			}
		};

		listeners[controllerName + 'Grid button[action=dbimport]'] = {
			click: function(button) {
				var grid = button.up('gridpanel');
				var selections = grid.getSelectionModel().getSelection();
				if (selections.length == 1) {
					var record = selections[0];
					var win = Ext.widget('idatabaseImportWindow', {
						__PROJECT_ID__: grid.__PROJECT_ID__,
						__COLLECTION_ID__: record.get('_id'),
						width: 480,
						height: 320
					});
					win.show();
				} else {
					Ext.Msg.alert('提示信息', '请选择一项您要编辑的集合');
				}
				return true;
			}
		};

		listeners[controllerName + 'Grid button[action=sync]'] = {
			click: function(button) {
				Ext.Msg.confirm('提示信息', '请确认你要同步当前插件的集合或者文档结构?', function(btn) {
					if (btn == 'yes') {
						var grid = button.up('gridpanel');
						Ext.Ajax.request({
							url: '/idatabase/collection/sync',
							params: {
								__PROJECT_ID__: grid.__PROJECT_ID__,
								__PLUGIN_ID__: grid.__PLUGIN_ID__
							},
							scope: me,
							success: function(response) {
								var text = Ext.JSON.decode(response.responseText, true);
								Ext.Msg.alert('提示信息', text.msg);
								grid.store.load();
							}
						});
					}
				}, me);
			}
		};

		listeners[controllerName + 'Grid button[action=hook]'] = {
			click: function(button) {
				Ext.Msg.confirm('提示信息', '请确认你要触发关联动作?', function(btn) {
					if (btn == 'yes') {
						var grid = button.up('gridpanel');
						var selections = grid.getSelectionModel().getSelection();
						if (selections.length == 1) {
							var record = selections[0];
							Ext.Ajax.request({
								url: '/idatabase/collection/hook',
								params: {
									__PROJECT_ID__: grid.__PROJECT_ID__,
									__PLUGIN_ID__: grid.__PLUGIN_ID__,
									__COLLECTION_ID__: record.get('_id')
								},
								scope: me,
								success: function(response) {
									var text = Ext.JSON.decode(response.responseText, true);
									Ext.Msg.alert('提示信息', text.msg);
								}
							});
						} else {
							Ext.Msg.alert('提示信息', '请选择一项您要编辑的集合');
						}
					}
				}, me);
			}
		};

		me.control(listeners);
	},
	buildDataPanel: function(grid, tabpanel, record) {
		var __PROJECT_ID__ = grid.__PROJECT_ID__;
		var __PLUGIN_ID__ = grid.__PLUGIN_ID__;
		var __COLLECTION_ID__ = record.get('_id');
		var __PLUGIN_COLLECTION_ID__ = record.get('plugin_collection_id');
		var collection_name = record.get('name');
		var isTree = Ext.isBoolean(record.get('isTree')) ? record.get('isTree') : false;
		var isRowExpander = Ext.isBoolean(record.get('isRowExpander')) ? record.get('isRowExpander') : false;
		var rowBodyTpl = record.get('rowExpanderTpl');
		var me = this;
		var panel = tabpanel.getComponent(__COLLECTION_ID__);
		if (panel == null) {
			// model的fields动态创建
			var modelFields = [];
			var searchFields = [{
				xtype: 'hiddenfield',
				name: '__PROJECT_ID__',
				value: __PROJECT_ID__,
				allowBlank: false
			}, {
				xtype: 'hiddenfield',
				name: '__COLLECTION_ID__',
				value: __COLLECTION_ID__,
				allowBlank: false
			}];

			var gridColumns = [];

			var structureStore = Ext.create('icc.store.idatabase.Structure');
			structureStore.proxy.extraParams = {
				__PROJECT_ID__: __PROJECT_ID__,
				__COLLECTION_ID__: __COLLECTION_ID__,
				__PLUGIN_ID__: __PLUGIN_ID__,
				__PLUGIN_COLLECTION_ID__: __PLUGIN_COLLECTION_ID__
			};

			var treeField = '';
			var treeLabel = '';
			structureStore.load(function(records, operation, success) {
				// 存储下拉菜单模式的列
				var gridComboboxColumns = [];
				var addOrEditFields = [];

				Ext.Array.forEach(records, function(record) {
					var isBoxSelect = Ext.isBoolean(record.get('isBoxSelect')) ? record.get('isBoxSelect') : false;
					var isLinkageMenu = Ext.isBoolean(record.get('isLinkageMenu')) ? record.get('isLinkageMenu') : false;
					var linkageClearValueField = Ext.isString(record.get('linkageClearValueField')) ? record.get('linkageClearValueField') : '';
					var linkageSetValueField = Ext.isString(record.get('linkageSetValueField')) ? record.get('linkageSetValueField') : '';
					var jsonSearch = Ext.isString(record.get('rshSearchCondition')) ? record.get('rshSearchCondition') : '';
					var cdnUrl = Ext.isString(record.get('cdnUrl')) ? record.get('cdnUrl') : '';
					var xTemplate = Ext.isString(record.get('xTemplate')) ? record.get('xTemplate') : '';

					// 获取fatherField
					if (record.get('rshKey')) {
						treeField = record.get('field');
						treeLabel = record.get('label');
					}

					var convertDot = function(name) {
						return name.replace(/\./g, '__DOT__');
					};

					var convertToDot = function(name) {
						return name.replace(/__DOT__/g, '.');
					};

					var recordType = record.get('type');
					var recordField = convertDot(record.get('field'));
					var recordLabel = record.get('label');
					var allowBlank = !record.get('required');

					// 创建添加和编辑的field表单开始
					var addOrEditField = {
						xtype: recordType,
						fieldLabel: recordLabel,
						name: recordField,
						allowBlank: allowBlank
					};

					switch (recordType) {
					case 'arrayfield':
					case 'documentfield':
						addOrEditField.xtype = 'textareafield';
						addOrEditField.name = recordField;
						break;
					case 'boolfield':
						delete addOrEditField.name;
						addOrEditField.radioName = recordField;
						break;
					case 'filefield':
						addOrEditField = {
							xtype: 'filefield',
							name: recordField,
							fieldLabel: recordLabel,
							labelWidth: 100,
							msgTarget: 'side',
							allowBlank: true,
							anchor: '100%',
							buttonText: '浏览本地文件'
						};
						break;
					case '2dfield':
						addOrEditField.title = recordLabel;
						addOrEditField.fieldName = recordField;
						break;
					case 'datefield':
						addOrEditField.format = 'Y-m-d H:i:s';
						break;
					case 'numberfield':
						addOrEditField.decimalPrecision = 8;
						break;
					case 'htmleditor':
						addOrEditField.height = 300;
						break;
					};

					var rshCollection = record.get('rshCollection');
					if (rshCollection != '') {
						var rshCollectionModel = 'rshCollectionModel' + rshCollection;
						var convert = function(value) {
							if (Ext.isObject(value)) {
								if (value['$id'] != undefined) {
									return value['$id'];
								} else if (value['sec'] != undefined) {
									var date = new Date();
									date.setTime(value['sec'] * 1000);
									return date;
								}
							} else if (Ext.isArray(value)) {
								return value.join(',');
							}
							return value;
						};

						Ext.define(rshCollectionModel, {
							extend: 'icc.model.common.Model',
							fields: [{
								name: record.get('rshCollectionDisplayField'),
								convert: convert
							}, {
								name: record.get('rshCollectionValueField'),
								convert: convert
							}]
						});

						var comboboxStore = Ext.create('Ext.data.Store', {
							model: rshCollectionModel,
							autoLoad: false,
							pageSize: 20,
							proxy: {
								type: 'ajax',
								url: '/idatabase/data/index',
								extraParams: {
									__PROJECT_ID__: __PROJECT_ID__,
									__COLLECTION_ID__: record.get('rshCollection'),
									jsonSearch: jsonSearch
								},
								reader: {
									type: 'json',
									root: 'result',
									totalProperty: 'total'
								}
							}
						});

						if (isBoxSelect) {
							addOrEditField.xtype = 'boxselect';
							addOrEditField.name = recordField + '[]';
							addOrEditField.multiSelect = true;
							addOrEditField.valueParam = 'idbComboboxSelectedValue';
							addOrEditField.delimiter = ',';
						} else {
							addOrEditField.xtype = 'combobox';
							addOrEditField.name = recordField;
							addOrEditField.multiSelect = false;
						}
						addOrEditField.fieldLabel = recordLabel;
						addOrEditField.store = comboboxStore;
						addOrEditField.queryMode = 'remote';
						addOrEditField.forceSelection = true;
						addOrEditField.editable = true;
						addOrEditField.minChars = 1;
						addOrEditField.pageSize = 20;
						addOrEditField.queryParam = 'search';
						addOrEditField.typeAhead = false;
						addOrEditField.valueField = record.get('rshCollectionValueField');
						addOrEditField.displayField = record.get('rshCollectionDisplayField');
						addOrEditField.fatherField = record.get('rshCollectionFatherField');
						addOrEditField.listeners = {
							select: function(combo, records, eOpts) {
								if (isLinkageMenu) {
									var value = [];
									if (records.length == 0 || linkageClearValueField == '' || linkageSetValueField == '') {
										return false;
									}

									Ext.Array.forEach(records, function(record) {
										value.push(record.get(combo.valueField));
									});

									var form = combo.up('form').getForm();

									var clearValueFields = linkageClearValueField.split(',');
									Ext.Array.forEach(clearValueFields, function(field) {
										var formField = form.findField(field) == null ? form.findField(field + '[]') : form.findField(field);
										if (formField != null) {
											formField.clearValue();
										}
									});


									var setValueFields = linkageSetValueField.split(',');
									Ext.Array.forEach(setValueFields, function(field) {
										var formField = form.findField(field) == null ? form.findField(field + '[]') : form.findField(field);
										if (formField != null) {
											var store = formField.store;
											var extraParams = store.proxy.extraParams;
											var linkageSearch = {};
											if (formField.fatherField != '') {
												linkageSearch[formField.fatherField] = {
													"$in": value
												};
												extraParams.linkageSearch = Ext.JSON.encode(linkageSearch);
											}
											store.load();
										}
									});

								}
								return true;
							}

						};
					}

					addOrEditFields.push(addOrEditField);
					// 创建添加和编辑的field表单结束

					// 创建model的fields开始
					var field = {
						name: convertToDot(recordField),
						type: 'string'
					};
					switch (recordType) {
					case 'arrayfield':
						field.convert = function(value, record) {
							if (Ext.isArray(value)) {
								return Ext.JSON.encode(value);
							} else {
								return value;
							}
						};
						break;
					case 'documentfield':
						field.convert = function(value, record) {
							if (Ext.isObject(value) || Ext.isArray(value)) {
								return Ext.JSON.encode(value);
							} else {
								return value;
							}
						};
						break;
					case '2dfield':
						field.convert = function(value, record) {
							if (Ext.isArray(value)) {
								return value.join(',');
							}
							return value;
						};
						break;
					case 'datefield':
						field.convert = function(value, record) {
							if (Ext.isObject(value) && value['sec'] != undefined) {
								var date = new Date();
								date.setTime(value.sec * 1000);
								return date;
							} else {
								return value;
							}
						};
						break;
					case 'numberfield':
						field.type = 'float';
						break;
					case 'boolfield':
						field.type = 'boolean';
						field.convert = function(value, record) {
							if (Ext.isBoolean(value)) {
								return value;
							} else if (Ext.isString(value)) {
								return value === 'true' || value === '√' ? true : false;
							}
							return value;
						};
						break;
					}
					modelFields.push(field);

					// 绘制grid的column信息
					if (record.get('main')) {
						var column = {
							text: recordLabel,
							dataIndex: convertToDot(recordField),
							flex: 1
						};

						if (xTemplate != '') {
							var column = {
								text: recordLabel,
								dataIndex: convertToDot(recordField),
								xtype: 'templatecolumn',
								tpl: xTemplate,
								flex: 1
							};
						}

						switch (recordType) {
						case 'boolfield':
							column.xtype = 'booleancolumn';
							column.trueText = '√';
							column.falseText = '×';
							column.field = {
								xtype: 'commonComboboxBoolean'
							};
							break;
						case '2dfield':
							column.align = 'center';
							break;
						case 'datefield':
							column.xtype = 'datecolumn';
							column.format = 'Y-m-d H:i:s';
							column.align = 'center';
							column.field = {
								xtype: 'datefield',
								allowBlank: allowBlank,
								format: 'Y-m-d H:i:s'
							};
							break;
						case 'numberfield':
							column.format = '0,000.00';
							column.align = 'right';
							column.field = {
								xtype: 'numberfield',
								allowBlank: allowBlank
							};
							break;
						case 'filefield':
							if (xTemplate != '') {
								if (record.get('showImage') != undefined && record.get('showImage') == true) {
									column.tpl = '<a href="' + cdnUrl + '{' + recordField + '}" target="_blank"><img src="' + cdnUrl + '{' + recordfield + '}?size=100x100" border="0" height="100" /></a>';
								} else {
									column.tpl = column.tpl.replace('{cdnUrl}', cdnUrl);
								}
							}
							break;
						default:
							column.field = {
								xtype: 'textfield',
								allowBlank: allowBlank
							};
							break;
						}

						// 存在关联集合数据，则直接采用combobox的方式进行显示
						if (rshCollection != '' && !isBoxSelect) {
							column.field = {
								xtype: 'combobox',
								typeAhead: true,
								store: comboboxStore,
								allowBlank: allowBlank,
								displayField: record.get('rshCollectionDisplayField'),
								valueField: record.get('rshCollectionValueField'),
								queryParam: 'search',
								minChars: 1
							};

							column.renderer = function(value) {
								var rec = comboboxStore.findRecord(record.get('rshCollectionValueField'), value, 0, false, false, true);
								if (rec != null) {
									return rec.get(record.get('rshCollectionDisplayField'));
								}
								return '';
							};

							gridComboboxColumns.push(column);
						}

						gridColumns.push(column);
					}

					// 创建model的fields结束

					// 创建条件检索form
					if (record.get('searchable') && recordType != 'filefield') {

						var rshCollection = record.get('rshCollection');

						// $not操作
						var exclusive = {
							fieldLabel: '非',
							name: 'exclusive__' + recordField,
							xtype: 'checkboxfield',
							width: 30,
							inputValue: true,
							checked: false
						};

						// 开启精确匹配
						var exactMatch = {
							fieldLabel: '等于',
							name: 'exactMatch__' + recordField,
							xtype: 'checkboxfield',
							width: 30
						};

						if (rshCollection != '') {
							var comboboxSearchStore = Ext.create('Ext.data.Store', {
								model: rshCollectionModel,
								autoLoad: false,
								pageSize: 20,
								proxy: {
									type: 'ajax',
									url: '/idatabase/data/index',
									extraParams: {
										__PROJECT_ID__: __PROJECT_ID__,
										__COLLECTION_ID__: record.get('rshCollection'),
										jsonSearch: jsonSearch
									},
									reader: {
										type: 'json',
										root: 'result',
										totalProperty: 'total'
									}
								}
							});

							comboboxSearchStore.addListener('load', function() {
								var rec = comboboxSearchStore.findRecord(record.get('rshCollectionValueField'), '', 0, false, false, true);
								if (rec == null) {
									var insertRecord = {};
									insertRecord[record.get('rshCollectionDisplayField')] = '无';
									insertRecord[record.get('rshCollectionValueField')] = '';
									comboboxSearchStore.insert(0, Ext.create(rshCollectionModel, insertRecord));
								}
								return true;
							});

							searchFieldItem = {
								xtype: 'combobox',
								name: recordField,
								fieldLabel: recordLabel,
								typeAhead: true,
								store: comboboxSearchStore,
								displayField: record.get('rshCollectionDisplayField'),
								valueField: record.get('rshCollectionValueField'),
								queryParam: 'search',
								minChars: 1
							};

							searchField = {
								xtype: 'fieldset',
								layout: 'hbox',
								title: recordLabel,
								fieldDefaults: {
									labelAlign: 'top',
									labelSeparator: ''
								},
								items: [exclusive, searchFieldItem]
							};
						} else if (recordType == 'datefield') {
							searchField = {
								xtype: 'fieldset',
								layout: 'hbox',
								title: recordLabel,
								defaultType: 'datefield',
								fieldDefaults: {
									labelAlign: 'top',
									labelSeparator: '',
									format: 'Y-m-d H:i:s'
								},
								items: [exclusive,
								{
									fieldLabel: '开始时间',
									name: recordField + '[start]'
								}, {
									fieldLabel: '截止时间',
									name: recordField + '[end]'
								}]
							};
						} else if (recordType == 'numberfield') {
							searchField = {
								xtype: 'fieldset',
								layout: 'hbox',
								title: recordLabel,
								defaultType: 'numberfield',
								fieldDefaults: {
									labelAlign: 'top',
									labelSeparator: ''
								},
								items: [exclusive,
								{
									fieldLabel: '最小值(>=)',
									name: recordField + '[min]'
								}, {
									fieldLabel: '最大值(<=)',
									name: recordField + '[max]'
								}]
							};
						} else if (recordType == '2dfield') {
							searchField = {
								xtype: 'fieldset',
								layout: 'hbox',
								title: recordLabel,
								defaultType: 'numberfield',
								fieldDefaults: {
									labelAlign: 'top',
									labelSeparator: ''
								},
								items: [{
									name: recordField + '[lng]',
									fieldLabel: '经度'
								}, {
									name: recordField + '[lat]',
									fieldLabel: '维度'
								}, {
									name: recordField + '[distance]',
									fieldLabel: '附近范围(km)'
								}]
							};
						} else if (recordType == 'boolfield') {
							searchField = {
								xtype: 'commonComboboxBoolean',
								fieldLabel: recordLabel,
								name: recordField
							};
						} else {
							searchField = {
								xtype: 'fieldset',
								layout: 'hbox',
								title: recordLabel,
								defaultType: 'textfield',
								fieldDefaults: {
									labelAlign: 'top',
									labelSeparator: ''
								},
								items: [exclusive, exactMatch,
								{
									name: recordField,
									fieldLabel: recordLabel
								}]
							};
						}

						searchFields.push(searchField);
					}
					// 创建条件检索form结束
				});

				// 完善树状结构
				gridColumns = Ext.Array.merge(gridColumns, [{
					text: "_id",
					sortable: false,
					dataIndex: '_id',
					flex: 1,
					editor: 'textfield',
					hidden: true
				}, {
					xtype: 'datecolumn',
					format: 'Y-m-d H:i:s',
					text: "创建时间",
					sortable: false,
					flex: 1,
					dataIndex: '__CREATE_TIME__'
				}, {
					xtype: 'datecolumn',
					format: 'Y-m-d H:i:s',
					text: "修改时间",
					sortable: false,
					flex: 1,
					dataIndex: '__MODIFY_TIME__',
					hidden: true
				}]);

				// 创建数据的model
				var dataModelName = 'dataModel' + __COLLECTION_ID__;
				modelFields.push({
					name: '__DOMAIN__',
					type: 'string',
					defaultValue : __DOMAIN__
				});
				modelFields.push({
					name: '__PROJECT_ID__',
					type: 'string',
					defaultValue : __PROJECT_ID__
				});
				modelFields.push({
					name: '__COLLECTION_ID__',
					type: 'string',
					defaultValue : __COLLECTION_ID__
				});
				var dataModel = Ext.define(dataModelName, {
					extend: 'icc.model.common.Model',
					fields: modelFields
				});

				// 加载数据store
				if (isTree) {
					gridColumns = Ext.Array.merge({
						xtype: 'treecolumn',
						text: treeLabel,
						flex: 2,
						sortable: false,
						dataIndex: treeField
					}, gridColumns);

					gridColumns = Ext.Array.filter(gridColumns, function(item, index, array) {
						if (item.xtype !== 'treecolumn' && item.dataIndex === treeField) {
							return false;
						}
						return true;
					});

					var dataStore = Ext.create('Ext.data.TreeStore', {
						model: dataModelName,
						autoLoad: false,
						proxy: {
							type: 'ajax',
							url: '/idatabase/data/tree',
							extraParams: {
								__PROJECT_ID__: __PROJECT_ID__,
								__COLLECTION_ID__: __COLLECTION_ID__,
								__PLUGIN_ID__: __PLUGIN_ID__
							}
						},
						folderSort: true
					});
				} else {
					var dataStore = Ext.create('Ext.data.Store', {
						model: dataModelName,
						autoLoad: false,
						pageSize: 20,
						proxy: {
							type: 'ajax',
							url: '/idatabase/data/index',
							extraParams: {
								__PROJECT_ID__: __PROJECT_ID__,
								__COLLECTION_ID__: __COLLECTION_ID__,
								__PLUGIN_ID__: __PLUGIN_ID__
							},
							reader: {
								type: 'json',
								root: 'result',
								totalProperty: 'total'
							}
						}
					});
				}

				panel = Ext.widget('idatabaseDataMain', {
					id: __COLLECTION_ID__,
					name: collection_name,
					title: collection_name,
					__COLLECTION_ID__: __COLLECTION_ID__,
					__PROJECT_ID__: __PROJECT_ID__,
					__PLUGIN_ID__: __PLUGIN_ID__,
					gridColumns: gridColumns,
					gridStore: dataStore,
					isTree: isTree,
					searchFields: searchFields,
					addOrEditFields: addOrEditFields,
					isRowExpander: isRowExpander,
					rowBodyTpl: rowBodyTpl
				});

				panel.on({
					beforerender: function(panel) {
						var grid = panel.down('grid') ? panel.down('grid') : panel.down('treepanel');
						grid.store.on('load', function(store, records, success) {
							if (success) {
								var loop = gridComboboxColumns.length;
								if (loop > 0) {
									Ext.Array.forEach(gridComboboxColumns, function(gridComboboxColumn) {
										var ids = [];
										for (var index = 0; index < records.length; index++) {
											ids.push(records[index].get(gridComboboxColumn.dataIndex));
										}
										ids = Ext.Array.unique(ids);

										var store = gridComboboxColumn.field.store;
										if (isTree) {
											store.proxy.extraParams.limit = 10000;
										} else {
											store.proxy.extraParams.idbComboboxSelectedValue = ids.join(',');
										}
										store.load(function() {
											loop -= 1;
											if (loop == 0) {
												grid.getView().refresh();
											}
										});
									});
								} else {
									grid.getView().refresh();
								}
							}
						});
						if (!isTree) {
							grid.store.load();
						}

					}
				});

				tabpanel.add(panel);
				tabpanel.setActiveTab(__COLLECTION_ID__);

			});
		} else {
			tabpanel.setActiveTab(__COLLECTION_ID__);
		}
		// grid.getSelectionModel().deselectAll();
	}
});