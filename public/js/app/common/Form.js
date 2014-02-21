Ext.define('icc.common.Form', {
	extend : 'Ext.form.Panel',
	alias : 'widget.iform',
	frame : false,
	autoScroll : true,
	bodyPadding : 5,
	url : '',
	defaultType: 'textfield',
	fieldDefaults : {
		labelAlign: 'left',
		labelWidth : 100,
		anchor : '100%'
	},
	items:[],
	buttons : [ {
		text : '重置',
		action : 'reset',
		handler: function() {
			Ext.Msg.confirm('提示信息','请您确认是否要重置表单中的全部内容?',function(btn){
				if (btn == 'yes') {
					this.up('form').getForm().reset();
				}
			},this);
            
        }
	}, {
		text : '提交',
		action : 'submit',
		formBind : true, //only enabled once the form is valid
		disabled: true
	}]
});