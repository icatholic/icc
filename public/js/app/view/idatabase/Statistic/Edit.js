Ext.define('icc.view.idatabase.Statistic.Edit', {
	extend : 'icc.common.Window',
	alias : 'widget.idatabaseStatisticEdit',
	title : '编辑统计信息',
	initComponent : function() {
		Ext.apply(this, {
			items : [ {
				xtype : 'iform',
				url : '/idatabase/statistic/edit',
				fieldDefaults : {
					labelAlign : 'left',
					labelWidth : 150,
					anchor : '100%'
				},
				items : [ {
					xtype : 'hiddenfield',
					name : '_id',
					fieldLabel : '_id',
					allowBlank : false
				},{
					xtype : 'hiddenfield',
					name : '__PROJECT_ID__',
					fieldLabel : '项目编号',
					allowBlank : false,
					value : this.__PROJECT_ID__
				}, {
					xtype : 'hiddenfield',
					name : '__COLLECTION_ID__',
					fieldLabel : '集合编号',
					allowBlank : false,
					value : this.__COLLECTION_ID__
				}, {
					name : 'name',
					fieldLabel : '统计名称',
					allowBlank : false
				}, {
					xtype : 'idatabaseStatisticComboboxSeries',
					name : 'seriesType'
				}, {
					xtype: 'numberfield',
					name: 'maxShowNumber',
					fieldLabel: '最大显示条数',
					minValue: 0,
					maxValue: 1000,
					value: 100
				}, {
					xtype : 'fieldset',
					title : '柱状图/线形图',
					collapsed : false,
					collapsible : true,
					items : [ {
						xtype : 'fieldset',
						title : 'Y轴设定(纵向)',
						collapsed : false,
						collapsible : true,
						items : [ {
							xtype : 'textfield',
							name : 'yAxisTitle',
							fieldLabel : 'Y轴名称',
							allowBlank : true
						}, {
							xtype : 'idatabaseStatisticComboboxMethod',
							name : 'yAxisType',
							fieldLabel : 'Y轴统计方法',
							allowBlank : true
						}, {
							xtype : 'idatabaseStructureFieldCombobox',
							name : 'yAxisField',
							fieldLabel : 'Y轴统计属性',
							allowBlank : true,
							__PROJECT_ID__ : this.__PROJECT_ID__,
							__COLLECTION_ID__ : this.__COLLECTION_ID__
						} ]
					}, {
						xtype : 'fieldset',
						title : 'X轴设定(横向)',
						collapsed : false,
						collapsible : true,
						items : [ {
							xtype : 'textfield',
							name : 'xAxisTitle',
							fieldLabel : 'X轴名称',
							allowBlank : true
						}, {
							xtype : 'idatabaseStatisticComboboxType',
							name : 'xAxisType',
							fieldLabel : 'X轴统计类型',
							allowBlank : true
						}, {
							xtype : 'idatabaseStructureFieldCombobox',
							name : 'xAxisField',
							fieldLabel : 'X轴统计属性',
							allowBlank : true,
							__PROJECT_ID__ : this.__PROJECT_ID__,
							__COLLECTION_ID__ : this.__COLLECTION_ID__
						} ]
					} ]
				}, {
					xtype : 'fieldset',
					title : '饼状图',
					collapsed : false,
					collapsible : true,
					items : [ {
						xtype : 'idatabaseStructureFieldCombobox',
						name : 'seriesField',
						fieldLabel : '统计属性',
						allowBlank : true,
						__PROJECT_ID__ : this.__PROJECT_ID__,
						__COLLECTION_ID__ : this.__COLLECTION_ID__
					} ]
				}, {
					xtype: 'fieldset',
					title: '控制面板参数(可选)',
					collapsed: false,
					collapsible: true,
					items: [{
						xtype: 'radiogroup',
						fieldLabel: '开启控制面板显示',
						defaultType: 'radiofield',
						layout: 'hbox',
						items: [{
							boxLabel: '是',
							name: 'isDashboard',
							inputValue: true
						}, {
							boxLabel: '否',
							name: 'isDashboard',
							inputValue: false,
							checked: true
						}]
					}, {
						xtype: 'textareafield',
						name: 'dashboardQuery',
						fieldLabel: '统计条件'
					}, {
						xtype: 'idatabaseStatisticComboboxPeriod',
						name: 'statisticPeriod',
						fieldLabel: '统计周期'
					}, {
						xtype: 'numberfield',
						name: 'colspan',
						fieldLabel: '合并列1-3',
						minValue: 1,
						maxValue: 3,
						value: 1
					}, {
						xtype: 'numberfield',
						name: 'interval',
						fieldLabel: '计划任务执行间隔',
						minValue: 3600,
						maxValue: 86400,
						value: 3600
					}]
				} ]
			} ]
		});

		this.callParent();
	}

});