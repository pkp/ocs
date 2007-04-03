package org.pkp.ocs;

import junit.framework.Test;
import junit.framework.TestSuite;

/**
 * Run all JUnit tests for OCS.
 */
public class AllTests extends TestSuite {
	public static Test suite() {
		TestSuite suite = new TestSuite();
		suite.addTestSuite(BaseUrlExists.class);
		return suite;
	}

	 public static void main( String[] args ) {
		junit.textui.TestRunner.run(suite());
	}
}

