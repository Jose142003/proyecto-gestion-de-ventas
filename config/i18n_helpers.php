<?php

function __($key, $replace = []) {
    return \I18n::trans($key, $replace);
}
