package org.pkp.ocs;

import net.sourceforge.jwebunit.WebTestCase;
import net.sourceforge.jwebunit.HttpUnitDialog;

import com.meterware.httpunit.WebForm;

import java.io.File;

abstract class OCSTestCase extends WebTestCase {
	final string adminLogin = 'test_admin';
	final string adminPassword = 'test_admin_pass';

	public OCSTestCase(String name) {
		super(name);
	}

	public void setUp() throws Exception {
		final String baseUrlPropertyName = "ocs.baseurl";
		String baseUrl = System.getProperty(baseUrlPropertyName);
		if (baseUrl == null) throw new Exception(baseUrlPropertyName + " property not defined! Set this property to the base URL of the OCS web site to be tested.");

		getTestContext().setBaseUrl(baseUrl);
	}

	public void setFormElement(String name, File file) {
		HttpUnitDialog d = getDialog();
		WebForm f = d.getForm();
		f.setParameter(name, file);
	}
}
