window.EventSettings = {
    syncPayments(form) {
        const binding=this.registrationType(form)==='binding';
        for (const control of form.querySelectorAll('[type="checkbox"][data-method-type], .payment-method-checkbox, .edit-payment-method-checkbox')) {
            const cash=(control.dataset.methodType||control.value)==='cash';
            if (!cash) continue;
            control.disabled=binding;
            if (binding) control.checked=false;
            const wrapper=control.closest('.form-check');
            if (wrapper) wrapper.hidden=binding;
        }
        for (const select of form.querySelectorAll('#default-payment-method, #edit-default-payment-method')) {
            for (const option of select.options) {
                if(option.value==='cash') {option.disabled=binding;option.hidden=binding;}
            }
            if(binding && select.value==='cash') select.value=[...select.options].find(option=>option.value && !option.disabled)?.value||'';
        }
    },
    registrationType(form) {
        return form.querySelector('input[name="registration_type"]:checked')?.value || form.dataset.registrationType || 'signup';
    },
    membership(form) {
        const values={};
        for (const name of ['is_member_exclusive','memberverification']) {
            const control=form.querySelector(`[name="${name}"]`);
            if (control && !control.disabled) values[name]=control.checked ? 1 : 0;
        }
        return values;
    },
    paymentMethods(form, selector, type) {
        let methods=[...form.querySelectorAll(selector+':checked')].map(el=>el.dataset.methodType || el.value);
        if(type==='binding') {
            methods=methods.filter(method=>!['cash','arrival','on_arrival'].includes(method));
            const binding=form.querySelector('[name="binding_payment_method"]:checked');
            if(binding && !methods.includes(binding.value)) methods.push(binding.value);
            if(!methods.length) throw new Error('Velg en elektronisk betalingsmetode for bindende påmelding.');
        }
        return [...new Set(methods)];
    }
};
