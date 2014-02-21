Ext.define('icc.view.idatabase.Data.Field.boolfield', {
	extend : 'Ext.form.RadioGroup',
	alias : 'widget.boolfield',
	fieldLabel : '是否选择boolean',
	radioName : 'booleanName',
	defaults : {
		flex : 1
	},
	layout : 'hbox',
	initComponent : function() {
		Ext.apply(this, {
			items : [ {
				boxLabel : '是',
				name : this.radioName,
				inputValue : true
			}, {
				boxLabel : '否',
				name : this.radioName,
				inputValue : false
			} ]
		});
		this.callParent();
	}
});