$(document).ready(function () {

    var $renameModal = $('#js-confirm-rename');
    var $renameModalLink = $('#js-confirm-link');
    var $deleteModal = $('#js-confirm-delete');
    var $displayModal = $('#js-display-image');

    var callback = function (key, opt) {
        switch (key) {
            case 'edit':
                var $renameModalButton = opt.$trigger.find(".js-rename-modal")
                renameFile($renameModalButton)
                $renameModal.modal("show");
                break;
            case 'link-edit':
                var $renameModalButtonLink = opt.$trigger.find(".js-rename-link")
                renameLink($renameModalButtonLink)
                $renameModalLink.modal("show");
                break;
            case 'delete':
                var $deleteModalButton = opt.$trigger.find(".js-delete-modal")
                deleteFile($deleteModalButton)
                $deleteModal.modal("show");
                break;
            case 'download':
                var $downloadButton = opt.$trigger.find(".js-download")
                downloadFile($downloadButton);
                break;
            case 'preview':
                var $previewModalButton = opt.$trigger.find(".js-open-modal")
                previewFile($previewModalButton)
                $displayModal.modal("show");
                break;
        }
    };

    $.contextMenu({
        selector: '.file',
        callback: callback,
        items: {
            "delete": {
                name: deleteMessage,
                icon: "far fa-trash-alt"
            },
            "edit": {
                name: renameMessage,
                icon: "far fa-edit"
            },
            "download": {
                name: downloadMessage,
                icon: "fas fa-download"
            },
        }
    });

    $.contextMenu({
        selector: '.link',
        callback: callback,
        items: {
            "delete": {
                name: deleteMessage,
                icon: "far fa-trash-alt"
            },
            "link-edit": {
                name: renameMessage,
                icon: "far fa-edit"
            },
        }
    });

    $.contextMenu({
        selector: '.img',
        callback: callback,
        items: {
            "delete": {
                name: deleteMessage,
                icon: "far fa-trash-alt"
            },
            "edit": {
                name: renameMessage,
                icon: "far fa-edit"
            },
            "download": {
                name: downloadMessage,
                icon: "fas fa-download"
            },
            "preview": {
                name: previewMessage,
                icon: "fas fa-eye"
            },
        }
    });
    $.contextMenu({
        selector: '.dir',
        callback: callback,
        items: {
            "delete": {
                name: deleteMessage,
                icon: "far fa-trash-alt"
            },
            "edit": {
                name: renameMessage,
                icon: "far fa-edit"
            },
        }
    });


    function deleteFile($deleteModalButton) {
        $('#form_deleteId').val($deleteModalButton.data('id'));
        $('#js-confirm-delete').find('form').attr('action', $deleteModalButton.data('href'));
    }

    function deleteFolder($deleteModalButton) {
        $('#form_deleteId').val($('.jstree-clicked').attr('id'));
        $('#js-confirm-delete').find('form').attr('action', $deleteModalButton.data('href'));
    }

    function renameLink($renameModalButton) {
        $('#form_rename').val($renameModalButton.data('rename'));
        $('#form_linkId').val($renameModalButton.data('linkid'));
        $('#form_url').val($renameModalButton.data('url'));
        $renameModalLink.find('form').attr('action', $renameModalButton.data('href'))
    }

    function renameFile($renameModalButton) {
        $('#form_name').val($renameModalButton.data('name'));
        $('#form_id').val($renameModalButton.data('id'));
        $('#form_extension').val($renameModalButton.data('extension'));
        $renameModal.find('form').attr('action', $renameModalButton.data('href'))
    }

    function renameFolder($renameModalButton) {
        $('#form_name').val($('.jstree-clicked')
            .clone()
            .children()
            .remove()
            .end()
            .text());
        $('#form_id').val($('.jstree-clicked').attr('id'));
        $renameModal.find('form').attr('action', $renameModalButton.data('href'))
    }

    function previewFile($previewModalButton) {
        var href = addParameterToURL($previewModalButton.data('href'), 'time=' + new Date().getTime());
        //$('#js-display-image').find('img').attr('src', href);
        window.open(href,"newwindow",'left=410,height=610,width=860,top=200');
    }

    function addParameterToURL(_url, param) {
        _url += (_url.split('?')[1] ? '&' : '?') + param;
        return _url;
    }

    function downloadFile($downloadButton) {
        $downloadButton[0].click();
    }

    function initTree(treedata) {
        $('#tree').jstree({
            'core': {
                'data': treedata,
                "check_callback": true
            }
        }).bind("changed.jstree", function (e, data) {
            if (data.node) {
                document.location = data.node.a_attr.href;
            }
        });
    }

    if (tree === true) {

        // sticky kit
        $("#tree-block").stick_in_parent();

        initTree(treedata);

    }
    $(document)
        // checkbox select all
        .on('click', '#select-all', function () {
            var checkboxes = $('#form-multiple-delete').find(':checkbox')
            if ($(this).is(':checked')) {
                checkboxes.prop('checked', true);
            } else {
                checkboxes.prop('checked', false);
            }
        })
        // delete modal buttons
        .on('click', '.js-delete-modal', function () {
            deleteFile($(this));
        })
        .on('click', '.js-delete-folder', function () {
            deleteFolder($(this));
        })
        // preview modal buttons
        .on('click', '.js-open-modal', function () {
            previewFile($(this));
        })
        // rename link modal buttons
        .on('click', '.js-rename-link', function () {
            renameLink($(this));
        })
        // rename modal buttons
        .on('click', '.js-rename-modal', function () {
            renameFile($(this));
        })
        .on('click', '.js-rename-folder', function () {
            renameFolder($(this));
        })
        // multiple delete modal button
        .on('click', '#js-delete-multiple-modal', function () {
            //get ID of all files to delete, then do multiple ajax.
            var deleteId = $('#form-multiple-delete input:checked').map(function(){
                return $(this).attr('data-id');
            }).get();//.join('|');
            console.log(deleteId);

            $('#form_deleteId').val(deleteId);
            $('#js-confirm-delete').find('form').attr('action', $('#js-delete-multiple-modal').data('href'));
        })
        // disable button when very box is unchecked
        .on('click', '#form-multiple-delete :checkbox', function () {
            var $jsDeleteMultipleModal = $('#js-delete-multiple-modal');
            if ($(".checkbox").is(':checked')) {
                $jsDeleteMultipleModal.removeClass('disabled');
            } else {
                $jsDeleteMultipleModal.addClass('disabled');
            }
        });

    // preselected
    $renameModal.on('shown.bs.modal', function () {
        $('#form_name').select().mouseup(function () {
            $('#form_name').unbind("mouseup");
            return false;
        });
    });
    $('#addFolder').on('shown.bs.modal', function () {
        $('#rename_name').select().mouseup(function () {
            $('#rename_name').unbind("mouseup");
            return false;
        });
    });


    // Module Tiny
    if (moduleName === 'tiny') {

        $('#form-multiple-delete').on('click', '.select', function () {
            var args = top.tinymce.activeEditor.windowManager.getParams();
            var input = args.input;
            var document = args.window.document;
            var divInputSplit = document.getElementById(input).parentNode.id.split("_");

            // set url
            document.getElementById(input).value = $(this).attr("data-path");

            // set width and height
            var baseId = divInputSplit[0] + '_';
            var baseInt = parseInt(divInputSplit[1], 10);

            divWidth = baseId + (baseInt + 3);
            divHeight = baseId + (baseInt + 5);

            document.getElementById(divWidth).value = $(this).attr("data-width");
            document.getElementById(divHeight).value = $(this).attr("data-height");

            top.tinymce.activeEditor.windowManager.close();
        });
    }

    // Module CKEditor
    if (moduleName === 'ckeditor') {
        $('#form-multiple-delete').on('click', '.select', function () {
            var url = new URL(window.location.href);
            var funcNum = url.searchParams.get("CKEditorFuncNum");
            var fileUrl = $(this).attr("data-path");
            window.opener.CKEDITOR.tools.callFunction(funcNum, fileUrl);
            window.close();
        });
    }

    // Global functions
    // display error alert
    function displayError(msg) {
        displayAlert('danger', msg)
    }

    // display success alert
    function displaySuccess(msg) {
        displayAlert('success', msg)
    }

    // file upload
    $('#fileupload').fileupload({
        dataType: 'json',
        processQueue: false,
        dropZone: $('#dropzone')
    }).on('fileuploaddone', function (e, data) {
        $.each(data.result.files, function (index, file) {
            if (file.url) {
                // Ajax update view

                $.ajax({
                    dataType: "json",
                    url: url,
                    type: 'POST'
                }).done(function (data) {
                    displaySuccess('<strong>' + file.name + '</strong> ' + successMessage)
                    // update file list
                    $('#form-multiple-delete').html(data.data);

                    lazy();

                    if (tree === true) {
                        $('#tree').data('jstree', false).empty();
                        initTree(data.treeData);
                    }

                    $('#select-all').prop('checked', false);
                    $('#js-delete-multiple-modal').addClass('disabled');
                }).fail(function (jqXHR, textStatus, errorThrown) {
                    displayError('<strong>Ajax call error :</strong> ' + jqXHR.status + ' ' + errorThrown)
                });

            } else if (file.error) {
                displayError('<strong>' + file.name + '</strong> ' + file.error);
                counter = 0;
            }
        });
    }).on('fileuploadfail', function (e, data) {
        counter = 0;
        $.each(data.files, function (index, file) {
            displayError('File upload failed.')
        });
    });


    function lazy() {
        $('.lazy').Lazy({});
    }

    lazy();

    /**Reload page when all uploads finished. */
    $(this).ajaxStop(function(){
        location.reload();
    });

});