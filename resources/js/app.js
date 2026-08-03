const digits = (value, length) => String(value ?? '').replace(/\D/g, '').slice(0, length);

const formatters = {
    cpf(value) {
        return digits(value, 11)
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    },

    cnpj(value) {
        return digits(value, 14)
            .replace(/^(\d{2})(\d)/, '$1.$2')
            .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1/$2')
            .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
    },

    phone(value) {
        const valueDigits = digits(value, 11);

        if (valueDigits.length <= 10) {
            return valueDigits
                .replace(/^(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{4})(\d{1,4})$/, '$1-$2');
        }

        return valueDigits
            .replace(/^(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{5})(\d{1,4})$/, '$1-$2');
    },
};

window.BelezaMasks = {
    ...formatters,

    apply(event, formatter) {
        const maskedValue = formatters[formatter](event.target.value);

        if (event.target.value === maskedValue) {
            return;
        }

        event.target.value = maskedValue;

        // Alpine and Livewire both listen to the input event. Dispatching a
        // second event guarantees that Livewire receives the final masked
        // value instead of the value that existed before Alpine formatted it.
        queueMicrotask(() => {
            event.target.dispatchEvent(new Event('input', { bubbles: true }));
        });
    },
};
