Ext.define('icc.view.idatabase.Data.Search', {
	extend : 'Ext.panel.Panel',
	alias : 'widget.idatabaseDataSearch',
	title : '数据检索 - 数据统计 - 数据导出',
	region : 'east',
	collapsible : true,
	collapsed : true,
	animCollapse: false,
	border : true,
	frame : false,
	autoScroll : true,
	initComponent : function() {
		this.searchFields.push({
			xtype : 'hiddenfield',
			name : '__PROJECT_ID__',
			value : this.__PROJECT_ID__
		});

		this.searchFields.push({
			xtype : 'fieldset',
			layout : 'hbox',
			title : '创建时间范围',
			defaultType : 'datefield',
			fieldDefaults : {
				labelAlign : 'top',
				labelSeparator : '',
				format : 'Y-m-d H:i:s'
			},
			items : [ {
				fieldLabel : '非',
				name : 'exclusive__' + '__CREATE_TIME__',
				xtype : 'checkboxfield',
				width : 30,
				inputValue : true,
				checked : false
			}, {
				fieldLabel : '开始时间',
				name : '__CREATE_TIME__[start]'
			}, {
				fieldLabel : '截止时间',
				name : '__CREATE_TIME__[end]'
			} ]
		}, {
			xtype : 'fieldset',
			layout : 'hbox',
			title : '修改时间范围',
			defaultType : 'datefield',
			fieldDefaults : {
				labelAlign : 'top',
				labelSeparator : '',
				format : 'Y-m-d H:i:s'
			},
			items : [ {
				fieldLabel : '非',
				name : 'exclusive__' + '__MODIFY_TIME__',
				xtype : 'checkboxfield',
				width : 30,
				inputValue : true,
				checked : false
			}, {
				fieldLabel : '开始时间',
				name : '__MODIFY_TIME__[start]'
			}, {
				fieldLabel : '截止时间',
				name : '__MODIFY_TIME__[end]'
			} ]
		});

		this.searchFields.push(Ext.create('icc.view.idatabase.Statistic.Combobox', {
			name : '__STATISTIC_ID__',
			__PROJECT_ID__ : this.__PROJECT_ID__,
			__COLLECTION_ID__ : this.__COLLECTION_ID__
		}));

		Ext.apply(this, {
			items : [ {
				xtype : 'iform',
				url : '/idatabase/data/index?action=search',
				items : this.searchFields,
				buttons : [ {
					text : '搜索',
					action : 'search'
				}, {
					text : '统计',
					action : 'statistic'
				}, {
					text : '导出',
					action : 'excel'
				} ]
			} ]
		});

		this.callParent();
	}

});