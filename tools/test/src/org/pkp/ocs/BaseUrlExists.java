package org.pkp.ocs;

public class BaseUrlExists extends OCSTestCase {

	public BaseUrlExists(String name) {
		super(name);
	}

	public void testBaseUrl() {
		beginAt("/");
		assertLinkPresentWithText("Open Conference Systems");
	}
}
