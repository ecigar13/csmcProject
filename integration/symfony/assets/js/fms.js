var fmsObject;

$(function(){
    fmsObject = new _fms();
})

var _fms = function(){
    var that = this;
    var ui = {
        move: {
            button: $('button.js-move-modal'),
            modal: $('#moveFile'),
            current: $('#moveFile .current'),
            current_id: $('#moveFile .current_file_id'),
            new_parent_id: $('#moveFile .new_parent_id'),
            destBox: $('#moveFile .dest'),
        }
    }

    var init = function(){
        setListeners();
    }

    var setListeners = function(){
        ui.move.button.on('click',moveFile);
    }

    var moveFile = function(){
        console.log($(this).data());
        var file_id = $(this).data('id');
        var file_name = `${$(this).data('name')}.${$(this).data('extension')}`;
        buildMoveSelect();     
        ui.move.current.val(file_name);
        ui.move.current_id.val(file_id);
        ui.move.modal.modal("show");
    }

    var buildMoveSelect = function(){
        var tree_array = getTreeArray([], treedata, '');
        var el = $(`<select class="target_folder" name="dest">`)
                .on('change', function(){
                    var new_parent_id = $(this).children("option:selected"). val();
                    ui.move.new_parent_id.val(new_parent_id);
                })
        if(tree_array.length > 0){
            ui.move.new_parent_id.val(tree_array[0].value);
        }
        for(var t of tree_array){
            $(`<option>`).text(t.text).val(t.value).appendTo(el);
        }
        console.log(el);
        ui.move.destBox.empty();
        ui.move.destBox.append(el);
    }

    var getTreeArray = function(tree_array = [], treedata, parent=''){
        for(var t of treedata){
            var path = `${parent}/${t.text}`;
            tree_array.push({
                text: path,
                value: t.id
            })
            if(t.children && t.children.length > 0){
                getTreeArray(tree_array, t.children, path);
            }
        }
        return tree_array;
    }

    init();
}