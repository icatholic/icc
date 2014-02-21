Ext.define('icc.controller.idatabase.Import', {
	extend: 'Ext.app.Controller',
	models: [],
	stores: [],
	views: ['idatabase.Import.Window'],
	controllerName: 'idatabaseImport',
	actions: {
		add: '/idatabase/mapping/add',
		edit: '/idatabase/mapping/edit',
		remove: '/idatabase/mapping/remove',
		save: '/idatabase/mapping/save'
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

		var listeners = {};

		listeners[controllerName + 'Window button[action=submit]'] = {
			click: function(button) {
				var form = button.up('form').getForm();
				if (form.isValid()) {
					form.submit({
						waitTitle: '系统提示',
						waitMsg: '系统处理中，请稍后……',
						success: function(form, action) {
							Ext.Msg.alert('成功提示', action.result.msg);
							me.activeDataGrid().store.load();
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

		me.control(listeners);
		return true;
	}
});