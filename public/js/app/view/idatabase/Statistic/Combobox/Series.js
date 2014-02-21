Ext.define('icc.view.idatabase.Statistic.Combobox.Series', {
	extend : 'icc.common.Combobox',
	alias : 'widget.idatabaseStatisticComboboxSeries',
	fieldLabel : '统计图表类型',
	name : 'type',
	store : 'idatabase.Statistic.Series',
	valueField : 'value',
	displayField : 'name',
	queryMode : 'remote',
	editable : false,
	typeAhead : false
});
