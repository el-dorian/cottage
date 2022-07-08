/*exported handleAjaxActivators */

$(function () {
});

function serialize(obj) {
    const str = [];
    for (let p in obj)
        if (obj.hasOwnProperty(p)) {
            str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
        }
    return str.join("&");
}

function sendAjax(method, url, callback, attributes, isForm) {
    showWaiter();
    ajaxDangerReload();
    // проверю, не является ли ссылка на арртибуты ссылкой на форму
    if (attributes && attributes instanceof jQuery && attributes.is('form')) {
        attributes = attributes.serialize();
    } else if (isForm) {
        attributes = $(attributes).serialize();
    } else {
        attributes = serialize(attributes);
    }
    if (method === 'get') {
        $.ajax({
            method: method,
            data: attributes,
            url: url
        }).done(function (e) {
            deleteWaiter();
            ajaxNormalReload();
            callback(e);
        }).fail(function (e) {// noinspection JSUnresolvedVariable
            ajaxNormalReload();
            deleteWaiter();
            checkMessages();
            if (e.responseJSON) {// noinspection JSUnresolvedVariable
                makeInformer('danger', 'Системная ошибка', e.responseJSON['message']);
            } else {
                makeInformer('info', 'Ответ системы', e.responseText);
                console.log(e);
            }
            //callback(false)
        });
    } else if (method === 'post') {
        $.ajax({
            data: attributes,
            method: method,
            url: url
        }).done(function (e) {
            deleteWaiter();
            normalReload();
            callback(e);
        }).fail(function (e) {// noinspection JSUnresolvedVariable
            deleteWaiter();
            normalReload();
            checkMessages();
            if (e['responseJSON']) {// noinspection JSUnresolvedVariable
                makeInformer('danger', 'Системная ошибка', e.responseJSON.message);
            } else {
                makeInformer('info', 'Ответ системы', e.responseText);
            }
            //callback(false)
        });
    }
}

function sendSilentAjax(method, url, callback, attributes, isForm) {
    // проверю, не является ли ссылка на арртибуты ссылкой на форму
    if (attributes && attributes instanceof jQuery && attributes.is('form')) {
        attributes = attributes.serialize();
    } else if (isForm) {
        attributes = $(attributes).serialize();
    } else {
        attributes = serialize(attributes);
    }
    if (method === 'get') {
        $.ajax({
            method: method,
            data: attributes,
            url: url
        }).done(function (e) {
            callback(e);
        }).fail(function (e) {// noinspection JSUnresolvedVariable
            checkMessages();
            if (e['responseJSON']) {// noinspection JSUnresolvedVariable
                makeInformer('danger', 'Системная ошибка', e.responseJSON['message']);
            } else {
                makeInformer('info', 'Ответ системы', e.responseText);
            }
            callback(false)
        });
    } else if (method === 'post') {
        $.ajax({
            data: attributes,
            method: method,
            url: url
        }).done(function (e) {
            callback(e);
        }).fail(function (e) {// noinspection JSUnresolvedVariable
            checkMessages();
            if (e['responseJSON']) {// noinspection JSUnresolvedVariable
                makeInformer('danger', 'Системная ошибка', e.responseJSON.message);
            } else {
                makeInformer('info', 'Ответ системы', e.responseText);
            }
            callback(false)
        });
    }
}

// ========================================================== ИНФОРМЕР
// СОЗДАЮ ИНФОРМЕР
function makeInformer(type, header, body) {
    if (!body)
        body = '';
    const container = $('div#alertsContentDiv');
    const informer = $('<div class="alert-wrapper"><div class="alert alert-' + type + ' alert-dismissable my-alert"><div class="panel panel-' + type + '"><div class="panel-heading">' + header + '<button type="button" class="close">&times;</button></div><div class="panel-body">' + body + '</div></div></div></div>');
    informer.find('button.close').on('click.hide', function (e) {
        e.preventDefault();
        closeAlert(informer)
    });
    container.append(informer);
    showAlert(informer)
}

// ПОКАЗЫВАЮ ИНФОРМЕР
function showAlert(alertDiv) {
    // считаю расстояние от верха страницы до места, где располагается информер
    const topShift = alertDiv[0].offsetTop;
    const elemHeight = alertDiv[0].offsetHeight;
    let shift = topShift + elemHeight;
    alertDiv.css({'top': -shift + 'px', 'opacity': '0.1'});
    // анимирую появление информера
    alertDiv.animate({
        top: 0,
        opacity: 1
    }, 500, function () {
        // запускаю таймер самоуничтожения через 5 секунд
        setTimeout(function () {
            closeAlert(alertDiv)
        }, 30000);
    });

}

// СКРЫВАЮ ИНФОРМЕР
function closeAlert(alertDiv) {
    const elemWidth = alertDiv[0].offsetWidth;
    alertDiv.animate({
        left: elemWidth
    }, 500, function () {
        alertDiv.animate({
            height: 0,
            opacity: 0
        }, 300, function () {
            alertDiv.remove();
        });
    });
}

// Функция вызова пустого модального окна
function makeModal(header, text, delayed) {
    if (delayed) {
        console.log('make delayed');
        // открытие модали поверх другой модали
        let modal = $("div.modal");
        modal.off('hidden.bs.modal');
        if (modal.length === 1) {
            console.log('deleting old modal');
            modal.modal('hide');
            let newModal = $('<div id="myModal" class="modal fade mode-choose"><div class="modal-dialog  modal-lg"><div class="modal-content"><div class="modal-header">' + header + '</div><div class="modal-body">' + text + '</div><div class="modal-footer"><button class="btn btn-danger"  data-dismiss="modal" type="button" id="cancelActionButton">Отмена</button></div></div></div>');
            modal.on('hidden.bs.modal', function () {
                modal.remove();
                if (!text)
                    text = '';
                $('body').append(newModal);
                dangerReload();
                newModal.modal({
                    keyboard: true,
                    show: true
                });
                newModal.on('shown.bs.modal', function (){
                    console.log('modal showed');
                    $('body').css({'overflow' : 'hidden'});
                    $('div.wrap div.container, div.wrap nav').addClass('blured');
                });
                newModal.on('hidden.bs.modal', function () {
                    normalReload();
                    newModal.remove();
                    $('body').css({'overflow' : 'auto'});
                    $('div.wrap div.container, div.wrap nav').removeClass('blured');
                });
                $('div.wrap div.container, div.wrap nav').addClass('blured');
            });
            return newModal;
        }
    }
    if (!text)
        text = '';
    let modal = $('<div id="myModal" class="modal fade mode-choose"><div class="modal-dialog  modal-lg"><div class="modal-content"><div class="modal-header">' + header + '</div><div class="modal-body">' + text + '</div><div class="modal-footer"><button class="btn btn-danger"  data-dismiss="modal" type="button" id="cancelActionButton">Отмена</button></div></div></div>');
    $('body').append(modal);
    dangerReload();
    modal.modal({
        keyboard: true,
        show: true
    });
    modal.on('hidden.bs.modal', function () {
        normalReload();
        modal.remove();
        $('div.wrap div.container, div.wrap nav').removeClass('blured');
        console.log('modal deleted');
    });
    $('div.wrap div.container, div.wrap nav').addClass('blured');
    return modal;
}

function makeInformerModal(header, text, acceptAction, declineAction) {
    if (!text)
        text = '';
    let modal = $('<div class="modal fade mode-choose"><div class="modal-dialog text-center"><div class="modal-content"><div class="modal-header"><h3>' + header + '</h3></div><div class="modal-body">' + text + '</div><div class="modal-footer"><button class="btn btn-success" type="button" id="acceptActionBtn">Ок</button></div></div></div>');
    $('body').append(modal);
    let acceptButton = modal.find('button#acceptActionBtn');
    if (declineAction) {
        let declineBtn = $('<button class="btn btn-warning" role="button">Отмена</button>');
        declineBtn.insertAfter(acceptButton);
        declineBtn.on('click.custom', function () {
            normalReload();
            modal.modal('hide');
            declineAction();
        });
    }
    dangerReload();
    modal.modal({
        keyboard: false,
        backdrop: 'static',
        show: true
    });
    modal.on('hidden.bs.modal', function () {
        normalReload();
        modal.remove();
        $('div.wrap div.container, div.wrap nav').removeClass('blured');
    });
    modal.on('shown.bs.modal', function () {
        acceptButton.focus();
    });
    $('div.wrap div.container, div.wrap nav').addClass('blured');

    acceptButton.on('click', function () {
        normalReload();
        modal.modal('hide');
        if (acceptAction) {
            acceptAction();
        } else {
            location.reload();
        }
    });

    return modal;
}

function loadForm(url, modal, postUrl) {
    sendAjax('get', url, appendForm);

    function appendForm(form) {
        let ready = false;
        const frm = $(form.data);
        frm.find('button.popover-btn').popover({trigger: 'focus'});
        modal.find('div.modal-body').append(frm);
        frm.on('afterValidate', function (event, fields, errors) {
            ready = !errors.length;
        });
        frm.on('submit.test', function (e) {
            e.preventDefault();
            if (ready) {
                // отправлю форму
                // заблокирую кнопку отправки, чтобы невозможно было отправить несколько раз
                frm.find('button#addSubmit').addClass('disabled').prop('disabled', true);
                let i = 0;
                let loadedForm;
                while (frm[i]) {
                    if (frm[i].nodeName === "FORM") {
                        loadedForm = frm[i];
                        break;
                    }
                    i++;
                }
                sendAjax('post', postUrl, answerMe, loadedForm, true);

                function answerMe(e) {
                    normalReload();
                    if (e && e.status === 1) {
                        // успешно добавлено, перезагружаю страницу
                        location.reload();
                    } else if (e && e.status === 0) {
                        // получаю список ошибок, вывожу его
                        let errorsList = '';
                        for (let i in e['errors']) {
                            if (e['errors'].hasOwnProperty(i))
                                errorsList += e['errors'][i] + '\n';
                        }
                        makeInformer('danger', 'Сохранение не удалось.', errorsList);
                        modal.hide();
                    }
                }
            }
        });
    }
}

function handleErrors(errors) {
    let content = '';
    for (let i in errors) {
        if (errors.hasOwnProperty(i))
            content += errors[i][0]
    }
    return content;
}

function makeNewWindow(url, link, closeCallback) {
    if (link)
        link.close();
    link = window.open(url, '_blank');
    link.focus();
    $(link).on('load', function () {
        $(link).on('unload.call', function () {
            if (closeCallback)
                closeCallback();
        })
    });
    return link;
}

function disableElement(elem, newText) {
    elem.addClass('disabled').prop('disabled', true);
    if (newText) {
        elem.attr('data-realname', elem.text()).text(newText);
    }
}

function enableElement(elem, newText) {
    elem.removeClass('disabled').prop('disabled', false);
    if (newText)
        elem.text(newText);
    else if (elem.attr('data-realname'))
        elem.text(elem.attr('data-realname'));
}

function toRubles(summ) {
    if (typeof (summ) === 'string')
        summ = summ.replace(',', '.');
    summ = parseFloat(summ);
    return parseFloat(summ.toFixed(2));
}

function checkRubles(summ) {
    if (typeof (summ) === 'string')
        summ = summ.replace(',', '.');
    summ = parseFloat(summ);
    if (summ > 0) {
        let parts = summ.toFixed(2);
        parts = parts.toString().split(".");
        return parts[0] + ' руб. ' + parts[1] + ' коп.';
    }
    return null;
}

function ajaxDangerReload() {
    $(window).on('beforeunload.ajax', function () {
        return "Необходимо заполнить все поля на странице!";
    });
}

function ajaxNormalReload() {
    $(window).off('beforeunload.ajax');
}

function dangerReload() {
    $(window).on('beforeunload.message', function () {
        return "Необходимо заполнить все поля на странице!";
    });
}

function normalReload() {
    $(window).off('beforeunload');
}

function showWaiter() {
    let shader = $('<div class="shader"></div>');
    $('body').append(shader).css({'overflow': 'hidden'});

    $('div.wrap, div.flyingSumm, div.modal').addClass('blured');
    shader.showLoading();
}

function deleteWaiter() {
    $('div.wrap, div.flyingSumm, div.modal').removeClass('blured');
    $('body').css({'overflow': ''});
    let shader = $('div.shader');
    if (shader.length > 0)
        shader.hideLoading().remove();
}


function stringify(data) {
    if (typeof data === 'string') {
        return data;
    } else if (typeof data === 'object') {
        let answer = '';
        for (let i in data) {
            answer += data[i] + '<br/>';
        }
        return answer;
    }
}

// ТИПИЧНАЯ ОБРАБОТКА ОТВЕТА AJAX
function simpleAnswerHandler(data) {
    if (data['status']) {
        if (data['status'] === 1) {
            let message = data['message'] ? data['message'] : 'Операция успешно завершена';
            makeInformerModal("Успешно", message);
        } else {
            makeInformer('info', 'Ошибка, статус: ' + data['status'], stringify(data['message']));
        }
    } else {
        makeInformer('alert', 'Ошибка', stringify(data));
    }
}

// ТИПИЧНАЯ ОБРАБОТКА ОТВЕТА AJAX
function simpleAnswerInformerHandler(data) {
    if (data['status']) {
        if (data['status'] === 1) {
            let message = data['message'] ? data['message'] : 'Операция успешно завершена';
            makeInformer('success', "Успешно", message);
        } else {
            makeInformer('info', 'Ошибка, статус: ' + data['status'], stringify(data['message']));
        }
    } else {
        makeInformer('alert', 'Ошибка', stringify(data));
    }
}

function simpleModalHandler(data) {
    if (data.status) {
        if (data.status === 1) {
            return makeModal(data.title, data.view, data.delay);
        } else {
            makeInformer('info', 'Ошибка, статус: ' + data['status'], stringify(data['message']));
        }
    } else {
        makeInformer('alert', 'Ошибка', stringify(data));
    }
    return null;
}

function simpleSendForm(form, url) {
    form.on('submit.send', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        sendAjax('post', url, simpleAnswerHandler, form, true);
        return false;
    });
}

// навигация по табам
function enableTabNavigation() {
    let url = location.href.replace(/\/$/, "");
    if (location.hash) {
        const hash = url.split("#");
        $('a[href="#' + hash[1] + '"]').tab("show");
        url = location.href.replace(/\/#/, "#");
        history.replaceState(null, null, url);
    }

    $('a[data-toggle="tab"]').on("click", function () {
        let newUrl;
        const hash = $(this).attr("href");
        if (hash === "#home") {
            newUrl = url.split("#")[0];
        } else {
            newUrl = url.split("#")[0] + hash;
        }
        history.replaceState(null, null, newUrl);
    });
}

function toMathRubles(value) {
    return value.replace(',', '.');
}

// скрою существующую модаль
function closeModal() {
    let modal = $("#myModal");
    if (modal.length === 1) {
        modal.modal('hide');
    }
}

// обработаю ответ на передачу формы через AJAX ========================================================================
function ajaxFormAnswerHandler(data) {
    "use strict";
    if (data.status === 1) {
        // если передана ссылка на скачивание файла- открою её в новом окне
        if (data.href) {
            // закрою модальное окно
            closeModal();
            console.log('saving file');
            for (let i = 0; i < data.href.length; i++) {
                let newWindow = window.open(data.href[i]);
            }
        } else {
            location.reload();
        }
    } else if (data.message) {
        makeInformer('danger', "Ошибка", data.message);
    }
}

// обработка формы, переданной через AJAX ==============================================================================
function handleModalForm(data) {
    "use strict";
    let readyToSend = false;
    if (data.status && data.status === 1) {
        let modal = makeModal(data.header, data.data);
        let form = modal.find('form');
        form.on('afterValidate', function (event, messages) {
            if (messages) {
                let key;
                for (key in messages) {
                    if (messages.hasOwnProperty(key)) {
                        if (messages[key].length > 0) {
                            readyToSend = false;
                            return;
                        }
                    }
                }
                readyToSend = true;
            }
        });
        // при подтверждении форму не отправляю, жду валидации
        form.on('submit.sendByAjax', function (e) {
            console.log('submit');
            e.preventDefault();
            console.log(readyToSend);
            if (readyToSend === true) {
                sendAjax('post',
                    form.attr('action'),
                    ajaxFormAnswerHandler,
                    form,
                    true);
                readyToSend = false;
            }
        });
    } else if (data.status && data.status === 2) {
        location.reload();
    }
}

// обработка формы, переданной через AJAX без валидации ===============================================================
function handleModalFormNoValidate(data) {
    console.log('click me');
    "use strict";
    if (data.status && data.status === 1) {
        let modal = makeModal(data.header, data.data);
        let form = modal.find('form');
        // при подтверждении форму не отправляю, жду валидации
        form.on('submit.sendByAjax', function (e) {
            console.log('submit it');
            e.preventDefault();
            sendAjax('post',
                form.attr('action'),
                ajaxFormAnswerHandler,
                form,
                true);
        });
    } else if (data.status && data.status === 2) {
        location.reload();
    }
}

// обработка активаторов AJAX-запросов =================================================================================
function handleAjaxActivators() {
    "use strict";
    // найду активаторы AJAX-запросов
    let activators = $('.activator');
    activators.off('click.request');
    activators.on('click.request', function () {
        console.log('activator');
        let action = $(this).attr('data-action');
        if (action) {
            // отправлю запрос на форму
            sendAjax(
                "get",
                action,
                handleModalFormNoValidate
            )
        } else {
            makeInformer(
                "danger",
                "Ошибка",
                "Кнопке не назначено действие"
            )
        }
    });
    // найду активаторы AJAX-запросов
    let postTriggers = $('.ajax-post-trigger');
    postTriggers.off('click.request');
    postTriggers.on('click.request', function (e) {
        e.preventDefault();
        let action = $(this).attr('data-action');
        if (action) {
            // отправлю запрос на форму
            sendAjax(
                "post",
                action,
                simpleModalHandler
            )
        } else {
            makeInformer(
                "danger",
                "Ошибка",
                "Кнопке не назначено действие"
            )
        }
    });
    // найду активаторы AJAX-запросов
    let getTriggers = $('.ajax-get-trigger');
    getTriggers.off('click.request');
    getTriggers.on('click.request', function (e) {
        e.preventDefault();
        let action = $(this).attr('data-action');
        if (action) {
            // отправлю запрос на форму
            sendAjax(
                "get",
                action,
                simpleModalHandler
            )
        } else {
            makeInformer(
                "danger",
                "Ошибка",
                "Кнопке не назначено действие"
            )
        }
    });
}

function handleTooltipEnabled() {
    $('.tooltip-enabled').tooltip();
}